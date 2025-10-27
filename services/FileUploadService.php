<?php

class FileUploadService {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;

    public function __construct() {
        $this->uploadDir = '../data/documents/';
        $this->allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
    }

    /**
     * Upload multiple files and return comma-separated file paths
     * @param array $files $_FILES array element (can be single file or multiple files)
     * @param string $subfolder Subfolder within upload directory
     * @param int $userId User ID for organizing files in user-specific folder
     * @return array Response array with success status and file paths/message
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

            // Create upload directory structure: subfolder/userId/
            $userFolder = $userId ? "user_{$userId}/" : '';
            $fullUploadDir = $this->uploadDir . $subfolder . $userFolder;
            if (!is_dir($fullUploadDir)) {
                if (!mkdir($fullUploadDir, 0755, true)) {
                    return [
                        'success' => false,
                        'message' => 'Failed to create upload directory'
                    ];
                }
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
                    $errors[] = "File '{$file['name']}' has invalid type. Only JPEG, PNG, and GIF images are allowed.";
                    continue;
                }

                // Validate file size
                if ($file['size'] > $this->maxFileSize) {
                    $errors[] = "File '{$file['name']}' size exceeds 5MB limit";
                    continue;
                }

                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '_' . $i . '.' . $extension;
                $filePath = $fullUploadDir . $filename;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $uploadedFiles[] = [
                        'file_path' => $filePath,
                        'filename' => $filename,
                        'original_name' => $file['name'],
                        'size' => $file['size'],
                        'mime_type' => $mimeType
                    ];
                } else {
                    $errors[] = "Failed to upload file '{$file['name']}'";
                }
            }

            if (empty($uploadedFiles)) {
                return [
                    'success' => false,
                    'message' => 'No files were successfully uploaded. ' . implode(' ', $errors)
                ];
            }

            // Return comma-separated file paths
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
     * Upload a file and return the file path
     * @param array $file $_FILES array element
     * @param string $subfolder Subfolder within upload directory
     * @param int $userId User ID for organizing files in user-specific folder
     * @return array Response array with success status and file path/message
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
                    'message' => 'Invalid file type. Only JPEG, PNG, and GIF images are allowed.'
                ];
            }

            // Validate file size
            if ($file['size'] > $this->maxFileSize) {
                return [
                    'success' => false,
                    'message' => 'File size exceeds 5MB limit'
                ];
            }

            // Create upload directory structure: subfolder/userId/
            $userFolder = $userId ? "user_{$userId}/" : '';
            $fullUploadDir = $this->uploadDir . $subfolder . $userFolder;
            if (!is_dir($fullUploadDir)) {
                if (!mkdir($fullUploadDir, 0755, true)) {
                    return [
                        'success' => false,
                        'message' => 'Failed to create upload directory'
                    ];
                }
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $fullUploadDir . $filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                return [
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'data' => [
                        'file_path' => $filePath,
                        'filename' => $filename,
                        'original_name' => $file['name'],
                        'size' => $file['size'],
                        'mime_type' => $mimeType
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to move uploaded file'
                ];
            }

        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a file
     * @param string $filePath Path to the file to delete
     * @return bool True on success, false on failure
     */
    public function deleteFile($filePath) {
        try {
            if (file_exists($filePath)) {
                return unlink($filePath);
            }
            return true; // File doesn't exist, consider it deleted
        } catch (Exception $e) {
            error_log("File deletion error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file URL for web access
     * @param string $filePath File path
     * @return string File URL
     */
    public function getFileUrl($filePath) {
        // Convert file path to URL
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
            $errors[] = 'Invalid file type. Only JPEG, PNG, and GIF images are allowed.';
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
     * Upload message attachment
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
                    'message' => 'Invalid file type. Only JPEG, PNG, and GIF images are allowed.'
                ];
            }

            // Validate file size
            if ($file['size'] > $this->maxFileSize) {
                return [
                    'success' => false,
                    'message' => 'File size exceeds 5MB limit'
                ];
            }

            // Create upload directory structure: messages/transactionId/
            $fullUploadDir = $this->uploadDir . 'messages/transaction_' . $transactionId . '/';
            if (!is_dir($fullUploadDir)) {
                if (!mkdir($fullUploadDir, 0755, true)) {
                    return [
                        'success' => false,
                        'message' => 'Failed to create upload directory'
                    ];
                }
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $fullUploadDir . $filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                return [
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'file_path' => $filePath,
                    'original_name' => $file['name'],
                    'file_size' => $file['size']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to move uploaded file'
                ];
            }

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
