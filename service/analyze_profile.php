<?php
session_start();

function get_social_network($url) {
    if (strpos($url, 'test1.onlydeb.online') !== false) {
        return 'test1.onlydeb';
    } elseif (strpos($url, 'test2.onlydeb.online') !== false) {
        return 'test2.onlydeb';
    } elseif (strpos($url, 'test3.onlydeb.online') !== false) {
        return 'test3.onlydeb';
    } else {
        return null;
    }
}

function extract_user_id($url) {
    $parsed_url = parse_url($url);
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $params);
        if (isset($params['user_id'])) {
            return intval($params['user_id']);
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $profile_url = $_POST['profile_url'];
    $social_network = get_social_network($profile_url);

    if (!$social_network) {
        $error = "Неподдерживаемая социальная сеть.";
    } else {
        $user_id = extract_user_id($profile_url);

        if ($user_id <= 0) {
            $error = "Некорректный ID пользователя.";
        } else {
            $api_url = 'http://localhost:5000/analyze';
            $data = [
                'social_network' => $social_network,
                'user_id' => $user_id
            ];

            $options = [
                'http' => [
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($data),
                ],
            ];
            $context  = stream_context_create($options);
            $response = file_get_contents($api_url, false, $context);

            if ($response === FALSE) {
                $error = "Ошибка при вызове API.";
            } else {
                $results = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE || isset($results['error'])) {
                    $error = "Ошибка анализа: " . htmlspecialchars($response);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анализ профиля</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
        }
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card {
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #0d6efd;
            color: #fff;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
        }
        .card-body {
            padding: 20px;
        }
        .sentiment-positive {
            color: #198754;
        }
        .sentiment-neutral {
            color: #ffc107;
        }
        .sentiment-negative {
            color: #dc3545;
        }
        #network {
            width: 100%;
            height: 400px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .form-container {
                padding: 15px;
            }
            .card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="form-container">
        <h2 class="text-center mb-4">Анализ профиля</h2>
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="profile_url" class="form-label">Введите ссылку на профиль:</label>
                <input type="url" class="form-control" id="profile_url" name="profile_url" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Анализировать</button>
        </form>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    </div>

    <?php if (isset($results)): ?>
        <h3 class="text-center mb-4">Результаты анализа</h3>

        <div class="card">
            <div class="card-header">Первый контур: Сканирование баз данных</div>
            <div class="card-body">
                <p><strong>Всего проверено постов:</strong> <?= htmlspecialchars($results['scan_results']['total_posts_scanned']) ?></p>
                <p><strong>Упоминания бизнесов:</strong></p>
                <ul class="list-unstyled">
                    <li><span class="sentiment-positive">✔️ Положительные:</span> <?= htmlspecialchars($results['scan_results']['mentions']['positive']) ?></li>
                    <li><span class="sentiment-neutral">⚠️ Нейтральные:</span> <?= htmlspecialchars($results['scan_results']['mentions']['neutral']) ?></li>
                    <li><span class="sentiment-negative">❌ Отрицательные:</span> <?= htmlspecialchars($results['scan_results']['mentions']['negative']) ?></li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Второй контур: Анализ бизнеса</div>
            <div class="card-body">
                <div id="network"></div>
                <script type="text/javascript">
                    var nodes = new vis.DataSet([
                        {id: 1, label: 'Целевой бизнес', group: 'target'},
                        <?php foreach ($results['business_results'] as $index => $company): ?>
                            {id: <?= $index + 2 ?>, label: '<?= addslashes($company['company']) ?>', group: 'competitor'},
                        <?php endforeach; ?>
                    ]);

                    var edges = [];
                    <?php foreach ($results['business_results'] as $index => $company): ?>
                        edges.push({from: 1, to: <?= $index + 2 ?>});
                    <?php endforeach; ?>

                    var container = document.getElementById('network');
                    var data = {
                        nodes: nodes,
                        edges: edges
                    };
                    var options = {
                        nodes: {
                            shape: 'dot',
                            size: 20,
                            font: {
                                size: 14,
                                color: '#000'
                            },
                            borderWidth: 2,
                            shadow: true
                        },
                        groups: {
                            target: {
                                color: {background: '#0d6efd', border: '#000'}
                            },
                            competitor: {
                                color: {background: '#ffc107', border: '#000'}
                            }
                        },
                        edges: {
                            width: 2,
                            color: {color: '#ccc'},
                            smooth: true
                        },
                        physics: {
                            enabled: true,
                            stabilization: {
                                iterations: 250
                            }
                        }
                    };
                    var network = new vis.Network(container, data, options);
                </script>
            </div>
        </div>

        <h3 class="text-center mb-4">Карточки конкурентов</h3>
        <div class="row">
            <?php foreach ($results['business_results'] as $company): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <?= htmlspecialchars($company['company']) ?>
                        </div>
                        <div class="card-body">
                            <p><strong>Лайки:</strong> <?= htmlspecialchars($company['total_likes']) ?></p>
                            <p><strong>Дизлайки:</strong> <?= htmlspecialchars($company['total_dislikes']) ?></p>
                            <p><strong>Посты:</strong> <?= htmlspecialchars($company['total_posts']) ?></p>
                            <p><strong>Тональность комментариев:</strong></p>
                            <ul class="list-unstyled">
                                <li><span class="sentiment-positive">✔️ Положительные:</span> <?= htmlspecialchars($company['sentiment_scores']['positive']) ?></li>
                                <li><span class="sentiment-neutral">⚠️ Нейтральные:</span> <?= htmlspecialchars($company['sentiment_scores']['neutral']) ?></li>
                                <li><span class="sentiment-negative">❌ Отрицательные:</span> <?= htmlspecialchars($company['sentiment_scores']['negative']) ?></li>
                            </ul>
                            <p><strong>Профили:</strong></p>
                            <ul>
                                <?php foreach ($company['profiles'] as $profile): ?>
                                    <li>
                                        <a href="<?= htmlspecialchars($profile['profile_url']) ?>" target="_blank">
                                            <?= htmlspecialchars($profile['name']) ?> (<?= htmlspecialchars($profile['city']) ?>)
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>