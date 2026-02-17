<script setup lang="ts">
import { details } from "@/services/api/quote";
import { ref, onMounted } from "vue";

const printDetails = ref({
  line1: "",
  line2: ""
});

const fetchDetails = async () => {
  try {
    const response: { data: Array<{ name: string; detail: string }> } = await details();
    
    if (response?.data) {
      const data = response.data;
      let firstPart = data[0].detail;

      const middlePart = data
        .slice(1, 6)
        .map(item => `${item.name}: ${item.detail}`)
        .join(", ");
      
      printDetails.value.line1 = `${firstPart}, ${middlePart}`;
      printDetails.value.line2 = data
        .slice(6)
        .map(item => `${item.name}: ${item.detail}`)
        .join(", ");
    }
  } catch (error) {
    console.error("Error fetching details:", error);
  }
};

onMounted(fetchDetails);
</script>

<template>
  <div>
    <p class="mb-0">{{ printDetails.line1 }}</p>
    <p class="mb-0">{{ printDetails.line2 }}</p>
  </div>
</template>

<style lang="scss">
.document-footer {
  position: absolute;
  bottom: 12px;
  left: 0;
  width: 100%;
  text-align: center;
  font-size: 12px;
  line-height: 1.5;
}
</style>
