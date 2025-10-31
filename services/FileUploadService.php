<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;
use Cloudinary\Cloudinary;

class FileUploadService {
    private $cloudinary;
    private $allowedTypes;
    private $maxFileSize;
    private $baseFolder;

    public function __construct() {
        $config = Configuration::instance();
        $config->cloud->cloudName = 'dwg8ccdzh';
        $config->cloud->apiKey = '983429476869458';
        $config->cloud->apiSecret = 'r5WsniAZ3NP_WWzbVsyZpU9CEFk';
        $config->url->secure = true;
        
        $this->cloudinary = new Cloudinary($config);
        $this->allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
        $this->baseFolder = 'sukliswap'; // Base folder for all uploads
    }

    /**
     * Upload multiple files to Cloudinary and return comma-separated URLs
     * @param array $files $_FILES array element (can be single file or multiple files)
     * @param string $subfolder Subfolder within upload directory
     * @param int $userId User ID for organizing files in user-specific folder
     * @return array Response array with success status and file URLs/message
     */
    public function uploadMultipleFiles($files, $subfolder = '', $userId = null) {
        try {
            $uploadedFiles = [];
            $errors = [];

            // Handle single file upload
            if (isset($files['name']) && !is_array($files['name'])) {
                $files = [
                    'name' => [$files['name']],
                    'type' => [$files['type']],
                    'tmp_name' => [$files['tmp_name']],
                    'error' => [$files['error']],
                    'size' => [$files['size']]
                ];
            }

            // Check if files were uploaded
            if (!isset($files) || empty($files['name']) || $files['error'][0] !== UPLOAD_ERR_OK) {
                return [
                    'success' => false,
                    'message' => 'No files uploaded or upload error occurred'
                ];
            }

            // Process each file
            for ($i = 0; $i < count($files['name']); $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                // Skip if no file uploaded for this index
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    continue;
                }

                // Validate file type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mimeType, $this->allowedTypes)) {
                    $errors[] = "File '{$file['name']}' has invalid type. Only JPEG, PNG, GIF, and WebP images are allowed.";
                    continue;
                }

                // Validate file size
                if ($file['size'] > $this->maxFileSize) {
                    $errors[] = "File '{$file['name']}' size exceeds 5MB limit";
                    continue;
                }

                // Generate unique filename and folder path
                $userFolder = $userId ? "user_{$userId}/" : '';
                $folderPath = trim($this->baseFolder . '/' . $subfolder . '/' . $userFolder, '/');
                $filename = time() . '_' . uniqid() . '_' . $i;
                $publicId = $folderPath . '/' . $filename;

                // Upload to Cloudinary
                try {
                    $uploadResult = $this->cloudinary->uploadApi()->upload(
                        $file['tmp_name'],
                        [
                            'public_id' => $publicId,
                            'folder' => $folderPath,
                            'resource_type' => 'image',
                            'transformation' => [
                                'quality' => 'auto',
                                'fetch_format' => 'auto'
                            ]
                        ]
                    );

                    $uploadedFiles[] = [
                        'file_path' => $uploadResult['secure_url'],
                        'filename' => $filename,
                        'original_name' => $file['name'],
                        'size' => $file['size'],
                        'mime_type' => $mimeType,
                        'public_id' => $uploadResult['public_id']
                    ];
                } catch (Exception $e) {
                    $errors[] = "Failed to upload file '{$file['name']}': " . $e->getMessage();
                }
            }

            if (empty($uploadedFiles)) {
                return [
                    'success' => false,
                    'message' => 'No files were successfully uploaded. ' . implode(' ', $errors)
                ];
            }

            // Return comma-separated Cloudinary URLs
            $filePaths = array_column($uploadedFiles, 'file_path');
            
            return [
                'success' => true,
                'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
                'data' => [
                    'file_paths' => implode(',', $filePaths),
                    'files' => $uploadedFiles,
                    'errors' => $errors
                ]
            ];

        } catch (Exception $e) {
            error_log("Multiple file upload error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload a file to Cloudinary and return the URL
     * @param array $file $_FILES array element
     * @param string $subfolder Subfolder within upload directory
     * @param int $userId User ID for organizing files in user-specific folder
     * @return array Response array with success status and file URL/message
     */
    public function uploadFile($file, $subfolder = '', $userId = null) {
        try {
            // Check if file was uploaded
            if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
                return [
                    'success' => false,
                    'message' => 'No file uploaded or upload error occurred'
                ];
            }

            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $this->allowedTypes)) {
                return [
                    'success' => false,
                    'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.'
                ];
            }

            // Validate file size
            if ($file['size'] > $this->maxFileSize) {
                return [
                    'success' => false,
                    'message' => 'File size exceeds 5MB limit'
                ];
            }

            // Generate unique filename and folder path
            $userFolder = $userId ? "user_{$userId}/" : '';
            $folderPath = trim($this->baseFolder . '/' . $subfolder . '/' . $userFolder, '/');
            $filename = time() . '_' . uniqid();
            $publicId = $folderPath . '/' . $filename;

            // Upload to Cloudinary
            $uploadResult = $this->cloudinary->uploadApi()->upload(
                $file['tmp_name'],
                [
                    'public_id' => $publicId,
                    'folder' => $folderPath,
                    'resource_type' => 'image',
                    'transformation' => [
                        'quality' => 'auto',
                        'fetch_format' => 'auto'
                    ]
                ]
            );

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'file_path' => $uploadResult['secure_url'],
                    'filename' => $filename,
                    'original_name' => $file['name'],
                    'size' => $file['size'],
                    'mime_type' => $mimeType,
                    'public_id' => $uploadResult['public_id']
                ]
            ];

        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a file from Cloudinary
     * @param string $filePath Cloudinary URL or public ID
     * @return bool True on success, false on failure
     */
    public function deleteFile($filePath) {
        try {
            // For old local file paths, return true (backward compatibility)
            if (strpos($filePath, '../data/') === 0 || strpos($filePath, 'data/') === 0) {
                return true;
            }
            
            // Only proceed with Cloudinary deletion if it's a Cloudinary URL
            if (strpos($filePath, 'https://res.cloudinary.com') !== 0) {
                return true; // Not a Cloudinary URL, nothing to delete
            }
            
            // Extract public ID from Cloudinary URL
            $publicId = $this->extractPublicIdFromUrl($filePath);
            if (!$publicId) {
                return false;
            }
            
            // Delete from Cloudinary
            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            
            return $result['result'] === 'ok' || $result['result'] === 'not found';
            
        } catch (Exception $e) {
            error_log("Cloudinary deletion error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract public ID from Cloudinary URL
     * @param string $url Cloudinary URL
     * @return string|false Public ID or false on failure
     */
    private function extractPublicIdFromUrl($url) {
        try {
            $urlParts = parse_url($url);
            if (!isset($urlParts['path'])) {
                return false;
            }
            
            // Split path into segments
            $pathParts = explode('/', $urlParts['path']);
            
            // Find the version number (starts with 'v')
            $versionIndex = -1;
            foreach ($pathParts as $index => $part) {
                if (strpos($part, 'v') === 0 && is_numeric(substr($part, 1))) {
                    $versionIndex = $index;
                    break;
                }
            }
            
            if ($versionIndex === -1 || $versionIndex >= count($pathParts) - 1) {
                return false;
            }
            
            // Get everything after the version number
            $publicIdParts = array_slice($pathParts, $versionIndex + 1);
            $publicIdWithExtension = implode('/', $publicIdParts);
            
            // Remove file extension
            $publicId = pathinfo($publicIdWithExtension, PATHINFO_DIRNAME) . '/' . 
                       pathinfo($publicIdWithExtension, PATHINFO_FILENAME);
            
            // Clean up any leading './'
            $publicId = ltrim($publicId, './');
            
            return $publicId;
            
        } catch (Exception $e) {
            error_log("Extract public ID error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file URL for web access
     * @param string $filePath Cloudinary URL or old local file path
     * @return string File URL
     */
    public function getFileUrl($filePath) {
        // If it's already a Cloudinary URL, return as-is
        if (strpos($filePath, 'https://res.cloudinary.com') === 0) {
            return $filePath;
        }
        
        // For backward compatibility with old local paths
        $url = str_replace('../', '', $filePath);
        $url = str_replace('\\', '/', $url);
        return $url;
    }

    /**
     * Validate image file
     * @param array $file $_FILES array element
     * @return array Response array with validation result
     */
    public function validateImage($file) {
        $errors = [];

        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'No file uploaded or upload error occurred';
            return [
                'valid' => false,
                'errors' => $errors
            ];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $errors[] = 'File size exceeds 5MB limit';
        }

        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            $errors[] = 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.';
        }

        // Additional image validation
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = 'File is not a valid image';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType,
            'image_info' => $imageInfo
        ];
    }

    /**
     * Upload message attachment to Cloudinary
     * @param array $file $_FILES array element
     * @param int $transactionId Transaction ID for organizing files
     * @return array Response array with success status and file info
     */
    public function uploadMessageAttachment($file, $transactionId) {
        try {
            // Check if file was uploaded
            if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
                return [
                    'success' => false,
                    'message' => 'No file uploaded or upload error occurred'
                ];
            }

            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $this->allowedTypes)) {
                return [
                    'success' => false,
                    'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.'
                ];
            }

            // Validate file size
            if ($file['size'] > $this->maxFileSize) {
                return [
                    'success' => false,
                    'message' => 'File size exceeds 5MB limit'
                ];
            }

            // Generate folder path and filename for Cloudinary
            $folderPath = $this->baseFolder . '/messages/transaction_' . $transactionId;
            $filename = time() . '_' . uniqid();
            $publicId = $folderPath . '/' . $filename;

            // Upload to Cloudinary
            $uploadResult = $this->cloudinary->uploadApi()->upload(
                $file['tmp_name'],
                [
                    'public_id' => $publicId,
                    'folder' => $folderPath,
                    'resource_type' => 'image',
                    'transformation' => [
                        'quality' => 'auto',
                        'fetch_format' => 'auto'
                    ]
                ]
            );

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_path' => $uploadResult['secure_url'],
                'original_name' => $file['name'],
                'file_size' => $file['size'],
                'public_id' => $uploadResult['public_id']
            ];

        } catch (Exception $e) {
            error_log("Message attachment upload error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ];
        }
    }
}
?>
