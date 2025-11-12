# Import Wizard Dependencies

## Already Available
The following dependencies are already installed in the project:
- ✅ **Pinia** (v2.3.0) - State management for the import wizard
- ✅ **Vue Router** (v4.5.0) - For routing to the import wizard
- ✅ **Vue i18n** (v11.0.1) - For Macedonian translations
- ✅ **Axios** (v0.30.0) - For API calls to import endpoints

## Optional Enhancements
If you want to enhance the file upload experience, you can install:

```bash
# For enhanced file upload with progress and drag-drop
npm install filepond vue-filepond filepond-plugin-file-validate-type filepond-plugin-file-validate-size

# For progress indicators (alternative to built-in progress)
npm install @inertiajs/progress
```

## FilePond Integration (Optional)
If you choose to install FilePond, replace the file upload in `Step1Upload.vue` with:

```vue
<script setup>
import { FilePond } from 'vue-filepond'
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type'
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size'

// Import FilePond styles
import 'filepond/dist/filepond.min.css'
</script>

<template>
  <FilePond
    :files="files"
    :accepted-file-types="acceptedFileTypes"
    :max-file-size="'50MB'"
    @processfile="handleFilePondUpload"
    @removefile="handleFilePondRemove"
  />
</template>
```

## Current Implementation
The current implementation uses:
- **Native HTML5 drag-and-drop** for file uploads
- **Built-in progress tracking** in the Pinia store
- **Custom file validation** for supported formats
- **Standard fetch/axios** for file uploads with progress

This provides a fully functional import wizard without additional dependencies.