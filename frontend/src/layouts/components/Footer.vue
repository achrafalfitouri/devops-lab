<script setup lang="ts">
const appVersion = import.meta.env.VITE_APP_VERSION
</script>

<template>
  <div class="h-100 d-flex align-center justify-md-space-between justify-center">
    <!-- 👉 Footer: left content -->
    <span class="d-flex align-center text-medium-emphasis">
  &copy;
  {{ new Date().getFullYear() }}
  Designed By
  <a
    href="#"
    target="_blank"
    rel="noopener noreferrer"
    class="text-primary ms-1"
  >Icone</a>
  <span class="ms-1">Developed By</span> <!-- Add a class or inline style for spacing -->
  <a
    href="#"
    target="_blank"
    rel="noopener noreferrer"
    class="text-primary ms-1"
  >0101Agency</a>
</span>

    <!-- 👉 Footer: right content -->
      <span class="d-md-flex gap-x-4 text-primary d-none">
      <div v-if="appVersion" class="nav-version">
        <span class="version-text">v{{ appVersion }}</span>
      </div>
      </span>
  </div>
</template>
<style lang="scss" scoped>
.nav-version {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px 16px;
  margin-top: auto;
  position: sticky;
  bottom: 0;
  background: inherit;
}

.version-text {
  font-size: 10px;
  color: rgba(var(--v-theme-on-surface), 0.38);
  font-weight: 400;
  letter-spacing: 0.5px;
  user-select: none;
}

</style>
