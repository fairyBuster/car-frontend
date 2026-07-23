<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCaptchaCode(int $length = 4): string
{
    $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $max = strlen($alphabet) - 1;
    $generated = '';

    for ($i = 0; $i < $length; $i++) {
        $generated .= $alphabet[random_int(0, $max)];
    }

    return $generated;
}

$_SESSION['captcha_code'] = generateCaptchaCode();
$code = strtoupper((string)$_SESSION['captcha_code']);

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$width = 120;
$height = 40;
$bgColors = ['#f6f3ff', '#eff6ff', '#f2fbf8', '#fff7ed'];
$textColors = ['#7a1e3a', '#201f45', '#225f49', '#0f4f77'];

$background = $bgColors[random_int(0, count($bgColors) - 1)];

$noiseLines = '';
for ($i = 0; $i < 4; $i++) {
    $x1 = random_int(0, $width - 1);
    $y1 = random_int(0, $height - 1);
    $x2 = random_int(0, $width - 1);
    $y2 = random_int(0, $height - 1);
    $stroke = sprintf('#%06X', random_int(0xAAAAAA, 0xEEEEEE));
    $noiseLines .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $stroke . '" stroke-width="1" />';
}

$noiseDots = '';
for ($i = 0; $i < 24; $i++) {
    $cx = random_int(2, $width - 2);
    $cy = random_int(2, $height - 2);
    $r = random_int(1, 2);
    $fill = sprintf('#%06X', random_int(0xCFCFCF, 0xF5F5F5));
    $noiseDots .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="' . $fill . '" />';
}

$letters = '';
$baseX = 13;
for ($i = 0; $i < strlen($code); $i++) {
    $char = substr($code, $i, 1);
    $x = $baseX + ($i * 26) + random_int(-2, 2);
    $y = random_int(26, 32);
    $rotation = random_int(-18, 18);
    $fill = $textColors[random_int(0, count($textColors) - 1)];
    $letters .= '<text x="' . $x . '" y="' . $y . '" transform="rotate(' . $rotation . ' ' . $x . ' ' . $y . ')" fill="' . $fill . '">' . $char . '</text>';
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
echo '<rect width="100%" height="100%" rx="6" fill="' . $background . '" />';
echo $noiseLines;
echo $noiseDots;
echo '<g font-family="Verdana, Geneva, Tahoma, sans-serif" font-size="30" font-weight="700">';
echo $letters;
echo '</g>';
echo '</svg>';
