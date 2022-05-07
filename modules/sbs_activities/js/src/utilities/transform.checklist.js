/**
 * Transform data coming from Drupal into
 * frontend-readable one.
 */
const transformChecklist = (data) => {
  return {
    id: data.id || null,
    title: data.title || "",
    description: data.description || "",
    suggestedItems: data.suggestedItems,
    suggestionsLabel: data.suggestionsLabel || "",
    initialItems: data.items
      ? data.items.map((item, index) => ({
          id: item.id || null,
          text: item.text || "",
          isCompleted: item.isCompleted || false,
          isNew: false,
          isFocused: false,
          internalId: index,
        }))
      : [],
  };
};

export { transformChecklist };
