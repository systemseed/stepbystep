/**
 * Transform data coming from Drupal into
 * frontend-readable one.
 */
const transformAudio = (data) => {
  return {
    id: data.id || null,
    title: data.title || "",
    description: data.description || "",
    audioUrl: data.audio_url || "",
  };
};

export { transformAudio };
