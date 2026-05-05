# Backend Upload Fix Progress

## Plan Steps:
- [x] 1. Create TODO_BACKEND.md ✓
- [x] 2. Update backend/lib/upload.php: Add debug print_r($_FILES), explicit $uploadDir = __DIR__ . \"/../uploads/\"; chmod, time()-basename filename, echo success/fail on move_uploaded_file ✓
- [ ] 3. Test: Start backend server if not running, visit http://localhost/HIRA/admin/product-form.php, upload image, check debug echoes and backend/uploads/
- [ ] 4. Verify API products.php returns correct image path, file exists
- [ ] 5. Clean up debug echoes, mark complete


