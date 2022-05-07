import { getUserId } from "@anu/utilities/settings";

/**
 * Transform data coming from Drupal into
 * frontend-readable one.
 */
const transformToolbox = (data) => {
  if (!data || !data.activities) {
    return {
      activities: [],
    };
  }

  const allProgress = {};

  const activities = data.activities.map((activity) => {
    const key = `Anu.progress.u${getUserId()}.course${activity.session}`;
    let isLocked = activity["is_locked"];
    let progress = {};
    if (allProgress[activity.session]) {
      progress = allProgress[activity.session];
    } else {
      const json = window.localStorage.getItem(key);
      if (json) {
        progress = JSON.parse(json);
        allProgress[activity.session] = progress;
      }
    }

    if (
      progress &&
      activity.prev_lesson_id &&
      progress[activity.prev_lesson_id]
    ) {
      isLocked = !progress[activity.prev_lesson_id].completed;
    }

    return {
      ...activity,
      is_locked: isLocked,
    };
  });

  return {
    activities,
  };
};

export { transformToolbox };
