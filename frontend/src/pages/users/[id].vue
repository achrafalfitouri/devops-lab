<script setup lang="ts">
import UserBioPanel from '@/views/user/view/UserBioPanel.vue'
import UserTabOverview from '@/views/user/view/UserTabOverview.vue'
import type { User } from '@services/models';
import { fetchUserById,fetchArchivedUserById } from '@services/api/user'
import { useArchiveStoreStore } from '@/stores/archive';

const route = useRoute('users-id')
const archiveStore = useArchiveStoreStore();


const userData = ref<{
  user: User | null
}>({ user: null })

const userTab = ref(null)

const tabs = [
  // { title: 'Aperçu', icon: 'tabler-user' },
]

// 👉 fetch user data by id
const fetchUserData = async () => {
  try {
    let data;
    const id = route.params.id
    const filters: any = {};
    if (archiveStore.isArchive) {
      filters.archive = archiveStore.isArchive
       data = await fetchArchivedUserById(id, filters);

    }
    else {
      data = await fetchUserById(id, filters);

    }
    userData.value.user = data
  } catch (error) {
    console.error("Error fetching user data:", error)
    userData.value.user = null
  }
}

onMounted(() => {
  fetchUserData()
});

</script>

<template>
  <div>
    <!-- 👉 Header  -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Profil de l’utilisateur 
        </h4>
        <div class="text-body-1">
          {{ userData.user?.code }}
        </div>
      </div>
    </div>
    <!-- 👉 User Profile  -->
    <VRow v-if="userData">
      <VCol v-if="userData" cols="12" md="5" lg="4">
        <UserBioPanel :user-data="userData.user" @user-updated="fetchUserData" />
      </VCol>
      <VCol cols="12" md="7" lg="8">
        <!-- <VTabs v-model="userTab" class="v-tabs-pill mb-3 disable-tab-transition">
          <VTab v-for="tab in tabs" :key="tab.title">
            <VIcon size="20" start :icon="tab.icon" />
            {{ tab.title }}
          </VTab>
        </VTabs> -->
        <VWindow v-model="userTab" class="disable-tab-transition" :touch="false">
          <VWindowItem>
            <UserTabOverview />
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>
    <div v-else>
      <VAlert type="error" variant="tonal">
        {{ route.params.id }} pas trouvé!
      </VAlert>
    </div>
  </div>
</template>
