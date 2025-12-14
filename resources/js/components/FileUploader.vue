<template>
  <div class="csv-upload">
    <div
      class="drop-zone"
      @dragover.prevent
      @drop.prevent="handleDrop"
      @click="fileInput.click()"
    >
      <p v-if="!file">Drag & drop CSV here or click to select</p>
      <p v-else>{{ file.name }} ({{ formatSize(file.size) }})</p>
    </div>

    <input
      ref="fileInput"
      type="file"
      accept=".csv"
      hidden
      @change="handleFileSelect"
    />

    <button
      class="upload-btn"
      :disabled="!file || loading"
      @click="upload"
    >
      {{ loading ? 'Uploading...' : 'Upload CSV' }}
    </button>

    <progress v-if="progress > 0" :value="progress" max="100"></progress>

    <p v-if="error" class="error">{{ error }}</p>
    <p v-if="success" class="success">CSV uploaded successfully!</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const file = ref(null)
const fileInput = ref(null)
const loading = ref(false)
const progress = ref(0)
const error = ref(null)
const success = ref(false)

const validate = (f) => {
  if (!f.name.endsWith('.csv')) {
    error.value = 'Only CSV files are allowed'
    return false
  }
  if (f.size > 5 * 1024 * 1024) {
    error.value = 'Max file size is 5MB'
    return false
  }
  return true
}

const handleFileSelect = (e) => {
  file.value = e.target.files[0]
 console.log(file.value)
  
}

const handleDrop = (e) => {
  reset()
  const f = e.dataTransfer.files[0]
  if (f && validate(f)) file.value = f
}

const upload = async () => {
  loading.value = true
  error.value = null
  success.value = false

  const formData = new FormData()
  console.log(file.value)
  formData.append('file', file.value)

  try {
    await axios.post('/api/csv-upload', formData,{
      onUploadProgress: (e) => {
        progress.value = Math.round((e.loaded * 100) / e.total)
      },
    })
    success.value = true
  } catch (e) {
    error.value = e.response?.data?.message || 'Upload failed'
  } finally {
    loading.value = false
  }
}

const reset = () => {
  error.value = null
  success.value = false
  progress.value = 0
}

const formatSize = (bytes) =>
  (bytes / 1024 / 1024).toFixed(2) + ' MB'
</script>


