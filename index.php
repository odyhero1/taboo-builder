<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli(
        '127.0.0.1',
        '',
        '',
        ''
    );
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo '<h1>MySQL connection failed</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}

$mysqli->query(
    'CREATE TABLE IF NOT EXISTS taboo_cards (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        main_word VARCHAR(255) NOT NULL,
        forbidden_1 VARCHAR(255) NOT NULL,
        forbidden_2 VARCHAR(255) NOT NULL,
        forbidden_3 VARCHAR(255) NOT NULL,
        forbidden_4 VARCHAR(255) NOT NULL,
        forbidden_5 VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$errors = [];

$formData = [
    'main_word' => '',
    'forbidden' => array_fill(0, 5, ''),
];

$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'delete') {
        $cardId = isset($_POST['card_id']) ? (int) $_POST['card_id'] : 0;
        if ($cardId > 0) {
            $stmt = $mysqli->prepare('DELETE FROM taboo_cards WHERE id = ?');
            $stmt->bind_param('i', $cardId);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'create' || $action === 'update') {
        $formData['main_word'] = trim((string) ($_POST['main_word'] ?? ''));
        $forbidden = $_POST['forbidden'] ?? [];
        $formData['forbidden'] = [];
        for ($i = 0; $i < 5; $i++) {
            $formData['forbidden'][$i] = trim((string) ($forbidden[$i] ?? ''));
        }

        if ($formData['main_word'] === '') {
            $errors[] = 'Please enter the main word for the card.';
        }

        foreach ($formData['forbidden'] as $index => $word) {
            if ($word === '') {
                $errors[] = 'Forbidden word ' . ($index + 1) . ' cannot be empty.';
            }
        }

        if (!$errors) {
            if ($action === 'create') {
                $stmt = $mysqli->prepare(
                    'INSERT INTO taboo_cards (main_word, forbidden_1, forbidden_2, forbidden_3, forbidden_4, forbidden_5)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'ssssss',
                    ucfirst($formData['main_word']),
                    $formData['forbidden'][0],
                    ucfirst($formData['forbidden'][1]),
                    ucfirst($formData['forbidden'][2]),
                    ucfirst($formData['forbidden'][3]),
                    ucfirst($formData['forbidden'][4])
                );
                $stmt->execute();
                $stmt->close();
            } else {
                $cardId = isset($_POST['card_id']) ? (int) $_POST['card_id'] : 0;
                if ($cardId > 0) {
                    $stmt = $mysqli->prepare(
                        'UPDATE taboo_cards
                         SET main_word = ?,
                             forbidden_1 = ?,
                             forbidden_2 = ?,
                             forbidden_3 = ?,
                             forbidden_4 = ?,
                             forbidden_5 = ?
                         WHERE id = ?'
                    );
                    $stmt->bind_param(
                        'ssssssi',
                        $formData['main_word'],
                        $formData['forbidden'][0],
                        $formData['forbidden'][1],
                        $formData['forbidden'][2],
                        $formData['forbidden'][3],
                        $formData['forbidden'][4],
                        $cardId
                    );
                    $stmt->execute();
                    $stmt->close();
                }
            }

            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editingCard = null;

if ($editingId) {
    $stmt = $mysqli->prepare(
        'SELECT id, main_word, forbidden_1, forbidden_2, forbidden_3, forbidden_4, forbidden_5
         FROM taboo_cards WHERE id = ?'
    );
    $stmt->bind_param('i', $editingId);
    $stmt->execute();
    $stmt->bind_result(
        $cardId,
        $mainWord,
        $forbidden1,
        $forbidden2,
        $forbidden3,
        $forbidden4,
        $forbidden5
    );
    if ($stmt->fetch()) {
        $editingCard = [
            'id' => $cardId,
            'main_word' => $mainWord,
            'forbidden_1' => $forbidden1,
            'forbidden_2' => $forbidden2,
            'forbidden_3' => $forbidden3,
            'forbidden_4' => $forbidden4,
            'forbidden_5' => $forbidden5,
        ];
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $formData = [
                'main_word' => $mainWord,
                'forbidden' => [
                    $forbidden1,
                    $forbidden2,
                    $forbidden3,
                    $forbidden4,
                    $forbidden5,
                ],
            ];
        }
    }
    $stmt->close();
}

$cards = [];
$result = $mysqli->query(
    'SELECT id, main_word, forbidden_1, forbidden_2, forbidden_3, forbidden_4, forbidden_5, created_at
     FROM taboo_cards
     ORDER BY created_at DESC'
);
$counter = 0;
while ($row = $result->fetch_assoc()) {
    $counter ++;
    $cards[] = $row;
}
$result->free();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Taboo Card Builder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f5f7;
            margin: 0;
            padding: 40px;
            color: #333;
        }
        h1 {
            text-align: center;
            margin-bottom: 24px;
        }
        .card-form {
            max-width: 640px;
            margin: 0 auto 40px auto;
            background: #ffffff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        }
        .card-form label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .card-form input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
        }
        .card-form button {
            display: inline-block;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            cursor: pointer;
            font-size: 16px;
        }
        .card-form button:hover {
            background: #1d4ed8;
        }
        .error-list {
            margin: 0 0 16px 0;
            padding: 12px 16px;
            list-style: none;
            border: 1px solid #fca5a5;
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 8px;
        }
        .card-preview-controls {
            max-width: 640px;
            margin: 0 auto 24px auto;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }
        .action-button {
            padding: 12px 20px;
            border: none;
            border-radius: 999px;
            background: #1f2937;
            color: #ffffff;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .action-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.18);
        }
        .card-preview {
            max-width: 420px;
            margin: 0 auto 32px auto;
            padding: 32px 28px;
            border-radius: 28px;
            background: #201958;
            box-shadow: 0 20px 40px rgba(19, 15, 46, 0.35);
            color: #ffffff;
            text-align: center;
        }
        .card-preview__title {
            margin: 0 0 24px 0;
            font-size: 42px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .card-preview__list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 16px;
            font-size: 22px;
            letter-spacing: 1px;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .taboo-card {
            background: #ffffff;
            border-radius: 16px;
            border: 3px solid #ef4444;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .taboo-card h3 {
            margin: 0 0 12px 0;
            font-size: 22px;
            text-align: center;
            color: #111827;
        }
        .taboo-card ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .taboo-card li {
            padding: 6px 8px;
            margin-bottom: 6px;
            background: #fef3c7;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
            font-weight: 600;
            color: #92400e;
        }
        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .card-actions form {
            margin: 0;
        }
        .card-actions button {
            width: 100%;
            padding: 8px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        .card-actions .edit-button {
            background: #3b82f6;
            color: #ffffff;
        }
        .card-actions .edit-button:hover {
            background: #2563eb;
        }
        .card-actions .delete-button {
            background: #ef4444;
            color: #ffffff;
        }
        .card-actions .delete-button:hover {
            background: #dc2626;
        }
        @media (max-width: 600px) {
            body {
                padding: 20px;
            }
            .card-form {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <h1>Taboo Card Builder <?php echo $counter; ?></h1>
    <form class="card-form" method="post" action="<?php echo h($_SERVER['PHP_SELF']); ?>">
        <?php if ($errors): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <input type="hidden" name="action" value="<?php echo $editingCard ? 'update' : 'create'; ?>">
        <?php if ($editingCard): ?>
            <input type="hidden" name="card_id" value="<?php echo (int) $editingCard['id']; ?>">
        <?php endif; ?>
        <label for="main_word">Main Word</label>
        <input type="text" id="main_word" name="main_word" value="<?php echo h($formData['main_word']); ?>" placeholder="Enter the main word" required>
        <?php for ($i = 0; $i < 5; $i++): ?>
            <label for="forbidden_<?php echo $i; ?>">Forbidden Word <?php echo $i + 1; ?></label>
            <input type="text" id="forbidden_<?php echo $i; ?>" name="forbidden[<?php echo $i; ?>]" value="<?php echo h($formData['forbidden'][$i]); ?>" placeholder="Enter forbidden word <?php echo $i + 1; ?>" required>
        <?php endfor; ?>
        <button type="submit"><?php echo $editingCard ? 'Update Card' : 'Add Card'; ?></button>
    </form>
    <section class="card-preview-controls">
        <button type="button" class="action-button" id="preview-card-button">Preview Latest Card</button>
        <button type="button" class="action-button" id="download-cards-button">Download Cards as PNG</button>
    </section>
    <section id="card-preview" class="card-preview" hidden>
        <h2 class="card-preview__title" id="preview-main-word"></h2>
        <ul class="card-preview__list" id="preview-forbidden-list"></ul>
    </section>
    <section class="cards-grid">
        <?php foreach ($cards as $card): ?>
            <article class="taboo-card">
                <div>
                    <h3><?php echo h($card['main_word']); ?></h3>
                    <ul>
                        <li><?php echo h($card['forbidden_1']); ?></li>
                        <li><?php echo h($card['forbidden_2']); ?></li>
                        <li><?php echo h($card['forbidden_3']); ?></li>
                        <li><?php echo h($card['forbidden_4']); ?></li>
                        <li><?php echo h($card['forbidden_5']); ?></li>
                    </ul>
                </div>
                <div class="card-actions">
                    <form method="get" action="<?php echo h($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" name="edit" value="<?php echo (int) $card['id']; ?>">
                        <button type="submit" class="edit-button">Edit</button>
                    </form>
                    <form method="post" action="<?php echo h($_SERVER['PHP_SELF']); ?>" onsubmit="return confirm('Delete this card?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="card_id" value="<?php echo (int) $card['id']; ?>">
                        <button type="submit" class="delete-button">Delete</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$cards): ?>
            <p>No cards yet. Add your first Taboo card above.</p>
        <?php endif; ?>
    </section>
    <script>
        (function () {
            const cards = <?php echo json_encode($cards, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            const previewButton = document.getElementById('preview-card-button');
            const downloadButton = document.getElementById('download-cards-button');
            const previewSection = document.getElementById('card-preview');
            const previewMain = document.getElementById('preview-main-word');
            const previewList = document.getElementById('preview-forbidden-list');

            function renderPreview(card) {
                if (!card) {
                    previewSection.hidden = true;
                    return;
                }
                previewMain.textContent = card.main_word;
                previewList.innerHTML = '';
                const forbiddenKeys = ['forbidden_1', 'forbidden_2', 'forbidden_3', 'forbidden_4', 'forbidden_5'];
                forbiddenKeys.forEach(function (key) {
                    const li = document.createElement('li');
                    li.textContent = card[key];
                    previewList.appendChild(li);
                });
                previewSection.hidden = false;
            }

            function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
                const words = (text || '').split(' ');
                let line = '';
                const lines = [];
                for (let n = 0; n < words.length; n++) {
                    const testLine = line ? line + ' ' + words[n] : words[n];
                    const metrics = ctx.measureText(testLine);
                    if (metrics.width > maxWidth && n > 0) {
                        lines.push(line);
                        line = words[n];
                    } else {
                        line = testLine;
                    }
                }
                if (line) {
                    lines.push(line);
                }
                lines.forEach(function (ln, index) {
                    ctx.fillText(ln, x, y + index * lineHeight);
                });
                return y + lines.length * lineHeight;
            }

            function renderCardToCanvas(card) {
                const width = 1080;
                const height = 1623;
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');

                ctx.fillStyle = '#201958';
                ctx.fillRect(0, 0, width, height);

                ctx.fillStyle = '#fcd34d';
                ctx.textAlign = 'center';
                ctx.font = 'bold 80px Arial';
                const mainWordY = wrapText(ctx, (card.main_word || '').toUpperCase(), width / 2, 260, width - 200, 80);

                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 64px Arial';
                const forbiddenKeys = ['forbidden_1', 'forbidden_2', 'forbidden_3', 'forbidden_4', 'forbidden_5'];
                let yPosition = mainWordY + 160;
                forbiddenKeys.forEach(function (key) {
                    yPosition = wrapText(ctx, (card[key] || '').toUpperCase(), width / 2, yPosition, width - 240, 74);
                    yPosition += 36;
                });
                return canvas;
            }

            function canvasToUint8Array(canvas) {
                return new Promise(function (resolve, reject) {
                    canvas.toBlob(function (blob) {
                        if (!blob) {
                            reject(new Error('Failed to generate image data.'));
                            return;
                        }
                        blob.arrayBuffer().then(function (buffer) {
                            resolve(new Uint8Array(buffer));
                        }).catch(reject);
                    }, 'image/png');
                });
            }

            const CRC32_TABLE = (function () {
                const table = new Uint32Array(256);
                for (let i = 0; i < 256; i++) {
                    let value = i;
                    for (let j = 0; j < 8; j++) {
                        if (value & 1) {
                            value = 0xedb88320 ^ (value >>> 1);
                        } else {
                            value = value >>> 1;
                        }
                    }
                    table[i] = value >>> 0;
                }
                return table;
            })();

            function crc32(bytes) {
                let crc = 0 ^ -1;
                for (let i = 0; i < bytes.length; i++) {
                    crc = (crc >>> 8) ^ CRC32_TABLE[(crc ^ bytes[i]) & 0xff];
                }
                return (crc ^ -1) >>> 0;
            }

            function getDosTimestamp(date) {
                let year = date.getFullYear();
                if (year < 1980) {
                    year = 1980;
                }
                const dosTime = (date.getHours() << 11) | (date.getMinutes() << 5) | Math.floor(date.getSeconds() / 2);
                const dosDate = ((year - 1980) << 9) | ((date.getMonth() + 1) << 5) | date.getDate();
                return { time: dosTime, date: dosDate };
            }

            const textEncoder = new TextEncoder();

            function createZip(entries) {
                const localParts = [];
                const centralParts = [];
                let offset = 0;
                let centralSize = 0;
                const now = getDosTimestamp(new Date());

                entries.forEach(function (entry) {
                    const nameBytes = textEncoder.encode(entry.name);
                    const dataBytes = entry.data;
                    const crc = crc32(dataBytes);

                    const localHeader = new ArrayBuffer(30);
                    const localView = new DataView(localHeader);
                    localView.setUint32(0, 0x04034b50, true);
                    localView.setUint16(4, 20, true);
                    localView.setUint16(6, 0, true);
                    localView.setUint16(8, 0, true);
                    localView.setUint16(10, now.time, true);
                    localView.setUint16(12, now.date, true);
                    localView.setUint32(14, crc, true);
                    localView.setUint32(18, dataBytes.length, true);
                    localView.setUint32(22, dataBytes.length, true);
                    localView.setUint16(26, nameBytes.length, true);
                    localView.setUint16(28, 0, true);

                    localParts.push(new Uint8Array(localHeader));
                    localParts.push(nameBytes);
                    localParts.push(dataBytes);

                    const centralHeader = new ArrayBuffer(46);
                    const centralView = new DataView(centralHeader);
                    centralView.setUint32(0, 0x02014b50, true);
                    centralView.setUint16(4, 20, true);
                    centralView.setUint16(6, 20, true);
                    centralView.setUint16(8, 0, true);
                    centralView.setUint16(10, 0, true);
                    centralView.setUint16(12, now.time, true);
                    centralView.setUint16(14, now.date, true);
                    centralView.setUint32(16, crc, true);
                    centralView.setUint32(20, dataBytes.length, true);
                    centralView.setUint32(24, dataBytes.length, true);
                    centralView.setUint16(28, nameBytes.length, true);
                    centralView.setUint16(30, 0, true);
                    centralView.setUint16(32, 0, true);
                    centralView.setUint16(34, 0, true);
                    centralView.setUint16(36, 0, true);
                    centralView.setUint32(38, 0, true);
                    centralView.setUint32(42, offset, true);

                    centralParts.push(new Uint8Array(centralHeader));
                    centralParts.push(nameBytes);

                    const localLength = 30 + nameBytes.length + dataBytes.length;
                    offset += localLength;
                    centralSize += 46 + nameBytes.length;
                });

                const endRecord = new ArrayBuffer(22);
                const endView = new DataView(endRecord);
                endView.setUint32(0, 0x06054b50, true);
                endView.setUint16(4, 0, true);
                endView.setUint16(6, 0, true);
                endView.setUint16(8, entries.length, true);
                endView.setUint16(10, entries.length, true);
                endView.setUint32(12, centralSize, true);
                endView.setUint32(16, offset, true);
                endView.setUint16(20, 0, true);

                return new Blob([].concat(localParts, centralParts, [new Uint8Array(endRecord)]), { type: 'application/zip' });
            }

            function slugify(value) {
                return (value || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            }

            async function downloadCardsAsPng() {
                if (!cards.length) {
                    alert('Add a card before downloading.');
                    return;
                }
                const originalText = downloadButton.textContent;
                downloadButton.textContent = 'Preparing download...';
                downloadButton.disabled = true;

                try {
                    const entries = [];
                    const filenameCounts = new Map();

                    for (let index = 0; index < cards.length; index++) {
                        const card = cards[index];
                        const canvas = renderCardToCanvas(card);
                        const pngBytes = await canvasToUint8Array(canvas);

                        const slug = slugify(card.main_word);
                        const baseName = slug ? 'taboo-card-' + slug : 'taboo-card-' + (index + 1);
                        const count = filenameCounts.get(baseName) || 0;
                        const suffix = count === 0 ? '' : '-' + (count + 1);
                        filenameCounts.set(baseName, count + 1);

                        entries.push({
                            name: baseName + suffix + '.png',
                            data: pngBytes,
                        });
                    }

                    const zipBlob = createZip(entries);
                    const url = URL.createObjectURL(zipBlob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'taboo-cards.zip';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                } catch (error) {
                    console.error(error);
                    alert('Something went wrong while preparing the download. Please try again.');
                } finally {
                    downloadButton.textContent = originalText;
                    downloadButton.disabled = false;
                }
            }

            previewButton.addEventListener('click', function () {
                if (!cards.length) {
                    alert('Add a card before previewing.');
                    return;
                }
                renderPreview(cards[0]);
            });

            downloadButton.addEventListener('click', function () {
                downloadCardsAsPng();
            });
        })();
    </script>
</body>
</html>
