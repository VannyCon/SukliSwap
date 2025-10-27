# File Organization Structure

## Overview
The system now organizes uploaded valid ID images by creating user-specific folders. This makes it much easier to manage and locate files for each user.

## Folder Structure

```
data/documents/
├── valid_ids/
│   ├── user_1/
│   │   ├── 68ff2f2816535_1761554216_0.png
│   │   ├── 68ff2f2816a57_1761554216_1.png
│   │   └── 68ff2f2816b89_1761554216_2.jpg
│   ├── user_2/
│   │   ├── 68ff2f2817c90_1761554217_0.png
│   │   └── 68ff2f2817d01_1761554217_1.jpg
│   └── user_3/
│       ├── 68ff2f2818e12_1761554218_0.png
│       ├── 68ff2f2818f23_1761554218_1.jpg
│       └── 68ff2f2819045_1761554218_2.png
```

## How It Works

### 1. User Registration Process
1. User fills out registration form with multiple valid ID images
2. System creates user account first to get user ID
3. Creates folder: `data/documents/valid_ids/user_{userId}/`
4. Uploads all images to the user-specific folder
5. Updates user record with comma-separated file paths

### 2. File Naming Convention
- Format: `{unique_id}_{timestamp}_{index}.{extension}`
- Example: `68ff2f2816535_1761554216_0.png`
- `unique_id`: Unique identifier
- `timestamp`: Unix timestamp when uploaded
- `index`: Order of files (0, 1, 2, etc.)
- `extension`: Original file extension

### 3. Database Storage
- `valid_id` field stores: `valid_ids/user_1/file1.jpg,valid_ids/user_1/file2.jpg`
- Comma-separated relative paths from the documents directory

### 4. File Access
- Individual files: `/api/serve_file.php?file=paths&index=0`
- All user files: `/api/get_user_valid_ids.php?user_id=1`

## Benefits

1. **Organization**: Each user's files are in their own folder
2. **Security**: Easier to manage permissions per user
3. **Maintenance**: Easy to clean up files for specific users
4. **Scalability**: Better performance with many users
5. **Debugging**: Easier to troubleshoot file issues

## Migration

For existing installations:
1. Run the database migration script
2. Existing files will continue to work
3. New uploads will use the organized structure

## File Management

### Adding Files
- Files are automatically organized by user ID
- Multiple files supported per user
- Automatic folder creation

### Deleting Files
- Individual files can be deleted
- Entire user folder can be removed
- Database cleanup handled automatically

### Backup
- Each user folder can be backed up independently
- Easy to restore specific user files
- Maintains organization structure
