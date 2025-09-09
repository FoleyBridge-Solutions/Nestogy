@props(['action' => '/upload', 'collection' => 'default', 'multiple' => false, 'accept' => '*'])

<div x-data="fileUpload()" class="file-upload-component">
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
        <input 
            type="file" 
            @change="uploadFile($event)" 
            {{ $multiple ? 'multiple' : '' }}
            accept="{{ $accept }}"
            class="hidden"
            x-ref="fileInput"
        >
        
        <div x-show="!uploading" @click="$refs.fileInput// MIGRATED: .addEventListener('click', )" class="cursor-pointer">
            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-6"></i>
            <p class="text-lg font-medium text-gray-600">Click to upload files</p>
            <p class="text-sm text-gray-500">or drag and drop</p>
        </div>
        
        <div x-show="uploading" class="py-6">
            <div class="spinner-border text-blue-600" role="status">
                <span class="visually-hidden">Uploading...</span>
            </div>
            <p class="mt-2 text-sm text-gray-600">Uploading files...</p>
        </div>
    </div>
    
    <div x-show="error" class="mt-6 px-6 py-6 rounded bg-red-100 border border-red-400 text-red-700" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <span x-text="error"></span>
    </div>
    
    <div x-show="files.length > 0" class="mt-6">
        <h6 class="font-medium text-gray-700 mb-2">Uploaded Files:</h6>
        <template x-for="(file, index) in files" :key="index">
            <div class="flex items-center justify-between p-2 border rounded mb-2">
                <div class="flex items-center">
                    <i class="fas fa-file mr-2 text-gray-500"></i>
                    <span x-text="file.name" class="text-sm"></span>
                </div>
                <button @click="removeFile(index)" class="btn px-4 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-outline-danger">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </template>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('fileUpload', () => ({
        uploading: false,
        error: null,
        files: [],
        
        async uploadFile(event) {
            const files = Array.from(event.target.files);
            if (!files.length) return;
            
            this.uploading = true;
            this.error = null;
            
            for (const file of files) {
                const formData = new FormData();
                formData.append('file', file);
                
                try {
                    const response = await fetch('{{ $action }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: formData
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        this.files.push(result.file);
                    } else {
                        this.error = result.error;
                    }
                } catch (error) {
                    this.error = 'Upload failed';
                }
            }
            
            this.uploading = false;
        },
        
        removeFile(index) {
            this.files.splice(index, 1);
        }
    }));
});
</script>
