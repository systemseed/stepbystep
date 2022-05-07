export const saveChecklist = (activityId, data) => {
  return fetch(Drupal.url("session/token"))
    .then((response) => response.text())
    .then((token) =>
      fetch(Drupal.url(`activities/checklist/${activityId}`), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": token,
        },
        body: JSON.stringify(data),
      })
    );
};
