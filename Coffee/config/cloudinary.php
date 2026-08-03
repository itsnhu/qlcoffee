<?php
/**
 * Cloudinary Configuration
 * Cấu hình lưu trữ hình ảnh trên Cloudinary
 */

// Cloudinary Credentials
define('CLOUDINARY_CLOUD_NAME', 'daytrfyrg');
define('CLOUDINARY_API_KEY', '784438178628159');
define('CLOUDINARY_API_SECRET', 'DHKWrW5-kS_ItxG1TibCZNEnGgM');

// Upload Settings
define('CLOUDINARY_UPLOAD_PRESET', 'pharmamanagernew');
define('CLOUDINARY_FOLDER', 'pharmamanagernew/medicines');

// Allowed file types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB

/**
 * Upload image to Cloudinary
 *
 * @param array $file - $_FILES array element
 * @param string $folder - Folder name on Cloudinary
 * @return array - ['success' => bool, 'url' => string, 'public_id' => string, 'error' => string]
 */
function uploadToCloudinary($file, $folder = CLOUDINARY_FOLDER) {
    // Validate file
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Không có file được upload'];
    }

    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Loại file không được hỗ trợ. Chỉ chấp nhận: JPG, PNG, GIF, WEBP'];
    }

    // Check file size
    if ($file['size'] > MAX_IMAGE_SIZE) {
        return ['success' => false, 'error' => 'File quá lớn. Kích thước tối đa: 5MB'];
    }

    // Prepare upload
    $timestamp = time();
    $publicId = $folder . '/' . uniqid('med_');

    // Create signature
    $params = [
        'folder' => $folder,
        'timestamp' => $timestamp,
        'transformation' => 'c_limit,w_800,h_800,q_auto'
    ];

    ksort($params);
    $signatureString = '';
    foreach ($params as $key => $value) {
        $signatureString .= $key . '=' . $value . '&';
    }
    $signatureString = rtrim($signatureString, '&') . CLOUDINARY_API_SECRET;
    $signature = sha1($signatureString);

    // Upload using cURL
    $uploadUrl = 'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/upload';

    $postFields = [
        'file' => new CURLFile($file['tmp_name'], $mimeType, $file['name']),
        'api_key' => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder' => $folder,
        'transformation' => 'c_limit,w_800,h_800,q_auto'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Cloudinary Upload cURL Error: " . $curlError);
        return ['success' => false, 'error' => 'Lỗi kết nối đến Cloudinary'];
    }

    $result = json_decode($response, true);

    if ($httpCode === 200 && isset($result['secure_url'])) {
        return [
            'success' => true,
            'url' => $result['secure_url'],
            'public_id' => $result['public_id']
        ];
    } else {
        $errorMsg = isset($result['error']['message']) ? $result['error']['message'] : 'Unknown error';
        error_log("Cloudinary Upload Error: " . $errorMsg);
        return ['success' => false, 'error' => 'Lỗi upload: ' . $errorMsg];
    }
}

/**
 * Delete image from Cloudinary
 *
 * @param string $publicId - Public ID of the image
 * @return bool
 */
function deleteFromCloudinary($publicId) {
    if (empty($publicId)) {
        return false;
    }

    $timestamp = time();
    $signatureString = 'public_id=' . $publicId . '&timestamp=' . $timestamp . CLOUDINARY_API_SECRET;
    $signature = sha1($signatureString);

    $deleteUrl = 'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/destroy';

    $postFields = [
        'public_id' => $publicId,
        'api_key' => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $deleteUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return isset($result['result']) && $result['result'] === 'ok';
}

/**
 * Get Cloudinary image URL with transformations
 *
 * @param string $url - Original URL
 * @param int $width - Width
 * @param int $height - Height
 * @return string
 */
function getCloudinaryUrl($url, $width = 300, $height = 300) {
    if (empty($url)) {
        return BASE_URL . '/assets/images/no-image.png';
    }

    // If it's already a Cloudinary URL, add transformation
    if (strpos($url, 'cloudinary.com') !== false) {
        // Insert transformation after /upload/
        return preg_replace(
            '/(\/upload\/)/',
            '$1c_fill,w_' . $width . ',h_' . $height . ',q_auto/',
            $url
        );
    }

    return $url;
}
?>
