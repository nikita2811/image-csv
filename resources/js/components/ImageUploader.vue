<template>
  <div
    class="dropzone"
    :class="{ active: isDragging }"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="onDrop"
    @click="fileInput.click()"
  >
    <input
      ref="fileInput"
      type="file"
      multiple
      accept="image/*"
      hidden
      @change="onSelect"
    />

    <p v-if="files.length === 0">
      Drag & drop images or click to browse
    </p>

    <div v-else class="previews">
      <div
        v-for="(item, index) in files"
        :key="item.id"
        class="preview"
      >
        <img :src="item.preview" />
        <button @click.stop="remove(index)">✕</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  files: {
    type: Array,
    required: true,
  }
})

const emit = defineEmits(['add', 'remove'])

const isDragging = ref(false)
const fileInput = ref(null)

function onDrop(e) {
  isDragging.value = false
  emit('add', e.dataTransfer.files)
}

function onSelect(e) {
  emit('add', e.target.files)
  e.target.value = null
}

function remove(index) {
  emit('remove', index)
}
</script>

<style scoped>
.dropzone {
  border: 2px dashed #bbb;
  border-radius: 12px;
  padding: 24px;
  cursor: pointer;
  min-height: 160px;
  text-align: center;
}

.dropzone.active {
  border-color: #6366f1;
  background: #eef2ff;
}

.previews {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
  gap: 12px;
}

.preview {
  position: relative;
}

.preview img {
  width: 100%;
  height: 110px;
  object-fit: cover;
  border-radius: 8px;
}

.preview button {
  position: absolute;
  top: 6px;
  right: 6px;
  background: rgba(0,0,0,0.6);
  border: none;
  color: #fff;
  border-radius: 50%;
  width: 22px;
  height: 22px;
}
</style>
