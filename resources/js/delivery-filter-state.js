export function resetProjectForYearChange(year, project, submitForm) {
    project.value = '';
    project.disabled = !year.value;
    project.dispatchEvent(new Event('change', { bubbles: true }));
    submitForm();
}
