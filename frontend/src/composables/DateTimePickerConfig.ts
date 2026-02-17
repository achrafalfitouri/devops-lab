import flatpickr from "flatpickr";
type FlatpickrInstance = flatpickr.Instance;

export const createDatePickerConfig = () => ({
  mode: "range",

  onChange: (
    selectedDates: Date[],
    dateStr: string,
    instance: FlatpickrInstance
  ) => {
    if (selectedDates.length === 1) {
      const clickedDate = selectedDates[0];
      
      const startDate = new Date(clickedDate.getFullYear(), clickedDate.getMonth(), clickedDate.getDate());
      
      const endDate = new Date(clickedDate.getFullYear(), clickedDate.getMonth(), clickedDate.getDate() + 6);
      
      instance.setDate([startDate, endDate], false);
    }
  }
});
