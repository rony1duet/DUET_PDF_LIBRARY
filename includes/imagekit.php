<?php

/**
 * ImageKit.io Helper Class
 * Handles file operations with ImageKit.io API
 */

class ImageKitHelper
{
    private $publicKey;
    private $privateKey;
    private $endpoint;

    /**
     * Initialize ImageKit
     */
    public function __construct()
    {
        // Check if ImageKit credentials are defined
        if (!defined('IMAGEKIT_PUBLIC_KEY') || !defined('IMAGEKIT_PRIVATE_KEY') || !defined('IMAGEKIT_ENDPOINT')) {
            throw new Exception('ImageKit configuration is missing');
        }

        $this->publicKey = IMAGEKIT_PUBLIC_KEY;
        $this->privateKey = IMAGEKIT_PRIVATE_KEY;
        $this->endpoint = IMAGEKIT_ENDPOINT;
    }
    /**     * Upload a file to ImageKit.io
     * 
     * @param string $localFilePath Path to local file
     * @param string $fileName Name for the file in ImageKit
     * @param string $folder Folder path in ImageKit (default: uploads for unified storage)
     * @param string $mimeType MIME type of the file
     * @return array ImageKit file data (path, url, fileId)
     */
    public function uploadFile($localFilePath, $fileName, $folder = 'uploads', $mimeType = null)
    {
        // Auto-detect MIME type if not provided
        if ($mimeType === null) {
            $mimeType = mime_content_type($localFilePath);
        }

        // All files go to the uploads folder for unified storage
        $fullPath = 'uploads/' . $fileName;

        // Prepare the file data
        $file = curl_file_create($localFilePath, $mimeType, $fileName);        // Prepare request data
        $data = [
            'file' => $file,
            'fileName' => $fileName,
            'folder' => $folder,
            'useUniqueFileName' => 'true',  // Allow ImageKit to generate unique names (must be string)
            'isPrivateFile' => 'false',     // Must be string, not boolean
        ];

        // Add tags based on file type
        if (strpos($mimeType, 'image/') === 0) {
            $data['tags'] = 'cover,image';
        } elseif ($mimeType === 'application/pdf') {
            $data['tags'] = 'book,pdf';
        }

        // Prepare the cURL request
        if (!function_exists('curl_init')) {
            throw new Exception('cURL is not available. Please enable cURL extension.');
        }

        $ch = curl_init();

        if ($ch === false) {
            throw new Exception('Failed to initialize cURL');
        }

        curl_setopt($ch, CURLOPT_URL, 'https://upload.imagekit.io/api/v1/files/upload');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Longer timeout for uploads
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->privateKey . ':')
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only

        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Check for cURL errors
        if ($curlError) {
            throw new Exception('cURL error: ' . $curlError);
        }

        // Check if the request was successful
        if ($httpCode != 200) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Unknown error';
            error_log('ImageKit upload error: ' . $response);
            throw new Exception('Failed to upload file to ImageKit: ' . $errorMessage);
        }

        // Decode the response
        $responseData = json_decode($response, true);

        if (!$responseData) {
            throw new Exception('Invalid response from ImageKit');
        }        // Return the path and URL
        return [
            'path' => $responseData['filePath'],  // Use actual ImageKit path
            'url' => $responseData['url'],
            'fileId' => $responseData['fileId'],
            'name' => $responseData['name'],      // Actual filename in ImageKit
            'size' => $responseData['size'] ?? 0
        ];
    }

    /**
     * Delete a file from ImageKit.io
     * 
     * @param string $fileId ImageKit file ID
     * @return bool Success status
     */
    public function deleteFile($fileId)
    {
        // Prepare the cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.imagekit.io/v1/files/' . $fileId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->privateKey . ':')
        ]);

        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check if the request was successful
        return $httpCode == 204;
    }

    /**
     * Get file details from ImageKit.io
     * 
     * @param string $fileId ImageKit file ID
     * @return array File details
     */
    public function getFileDetails($fileId)
    {
        // Check if curl is available
        if (!function_exists('curl_init')) {
            throw new Exception('cURL is not available. Please enable cURL extension.');
        }

        // Prepare the cURL request
        $ch = curl_init();

        if ($ch === false) {
            throw new Exception('Failed to initialize cURL');
        }

        curl_setopt($ch, CURLOPT_URL, 'https://api.imagekit.io/v1/files/' . $fileId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->privateKey . ':')
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only

        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Check for cURL errors
        if ($response === false || !empty($curlError)) {
            throw new Exception('cURL error: ' . $curlError);
        }

        // Check if the request was successful
        if ($httpCode != 200) {
            error_log('ImageKit file details error: HTTP ' . $httpCode . ' - ' . $response);
            throw new Exception('Failed to get file details from ImageKit: HTTP ' . $httpCode);
        }

        // Decode the response
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new Exception('Invalid JSON response from ImageKit');
        }

        return $decoded;
    }
    /**
     * Generate URL with transformations for a file
     * 
     * @param string $path File path in ImageKit
     * @param array $transformations Optional transformations
     * @return string Transformed URL
     */
    public function getUrl($path, $transformations = [])
    {
        // Base URL
        $url = $this->endpoint . '/' . $path;

        // Add transformations if any
        if (!empty($transformations)) {
            $transformString = '';
            foreach ($transformations as $key => $value) {
                $transformString .= $key . '-' . $value . ',';
            }
            $transformString = rtrim($transformString, ',');
            $url = $this->endpoint . '/tr:' . $transformString . '/' . $path;
        }

        return $url;
    }
    /**
     * Get optimized image URL with common transformations
     * 
     * @param string $path File path in ImageKit
     * @param int $width Desired width (optional)
     * @param int $height Desired height (optional)
     * @param string $quality Image quality (auto, 90, 80, etc.)
     * @param string $format Output format (auto, webp, jpg, png)
     * @return string Optimized image URL
     */
    public function getOptimizedImageUrl($path, $width = null, $height = null, $quality = 'auto', $format = 'auto')
    {
        $transformations = [];

        if ($width) {
            $transformations['w'] = $width;
        }

        if ($height) {
            $transformations['h'] = $height;
        }

        if ($quality !== 'auto') {
            $transformations['q'] = $quality;
        }

        if ($format !== 'auto') {
            $transformations['f'] = $format;
        }

        // Add progressive loading for better performance
        $transformations['pr'] = 'true';

        // Add automatic image optimization
        $transformations['ik-sdk'] = 'php';

        return $this->getUrl($path, $transformations);
    }

    /**
     * Check if ImageKit is configured properly
     */
    public static function isConfigured()
    {
        return defined('IMAGEKIT_PUBLIC_KEY') &&
            defined('IMAGEKIT_PRIVATE_KEY') &&
            defined('IMAGEKIT_ENDPOINT') &&
            !empty(IMAGEKIT_PUBLIC_KEY) &&
            !empty(IMAGEKIT_PRIVATE_KEY) &&
            !empty(IMAGEKIT_ENDPOINT);
    }
}
