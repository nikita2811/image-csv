<template>
    <div>
      <div><h3>Upload Csv files</h3></div>
        <FileUploader/>
    




        <div><h3>Upload Images</h3></div>
         <h6 class="success">{{ response }}</h6>
    <ImageUploader
      :files="files"
      @add="addFiles"
      @remove="removeFile"
    />

    <button
      class="upload-btn"
      :disabled="uploading"
      @click="startUpload"
    >
      Upload Images
    </button>
    
  </div>
    </template>
    <script setup>
import { ref } from 'vue'
import axios from 'axios'
import FileUploader from './FileUploader.vue'
import ImageUploader from './ImageUploader.vue'


const CHUNK_SIZE = 1024 * 1024 // 1MB

const files = ref([])
const uploading = ref(false)
const response = ref(null)


function addFiles(fileList) {
  Array.from(fileList).forEach(file => {
    if (!file.type.startsWith('image/')) return

    files.value.push({
      id: crypto.randomUUID(),
      file,
      preview: URL.createObjectURL(file),
      status: 'queued',
      progress: 0,
    })
  })
}

function removeFile(index) {
  URL.revokeObjectURL(files.value[index].preview)
  files.value.splice(index, 1)
}

async function startUpload() {
  uploading.value = true

  for (const item of files.value) {
    await uploadFile(item)
  }

  uploading.value = false
}
async function checksum(file) {
  const buffer = await file.arrayBuffer()
  const hash = await crypto.subtle.digest('SHA-256', buffer)
  return [...new Uint8Array(hash)].map(b => b.toString(16).padStart(2,'0')).join('')
}

async function uploadFile(item) {
  item.status = 'hashing'
  item.checksum = await generateChecksum(item.file)
 
   item.status = 'uploading'
  const uploadId = crypto.randomUUID()
  const totalChunks = Math.ceil(item.file.size / CHUNK_SIZE)

  for (let index = 0; index < totalChunks; index++) {
    const start = index * CHUNK_SIZE
    const end = Math.min(start + CHUNK_SIZE, item.file.size)

    const chunk = item.file.slice(start, end)

    const form = new FormData()
    form.append('upload_id', uploadId)
    form.append('chunk_index', index)
    form.append('total_chunks', totalChunks)
    form.append('original_name', item.file.name)
    form.append('file', chunk)
    form.append('checksum',item.checksum)
   

    await axios.post('/api/images/chunk', form).then((res)=>{
      response.value = res.data.status
    })

    item.progress = Math.round(((index + 1) / totalChunks) * 100)
  }

  

  item.status = 'completed'
}
async function generateChecksum(file) {
  const buffer = await file.arrayBuffer()
  const hashBuffer = await crypto.subtle.digest('SHA-256', buffer)
  const hashArray = Array.from(new Uint8Array(hashBuffer))
  return hashArray.map(b => b.toString(16).padStart(2, '0')).join('')
}
</script>



  
    