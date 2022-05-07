export const decodeHTMLEntities = (string) => {
  if (!string || typeof string !== "string") {
    return;
  }
  const doc = new DOMParser().parseFromString(string, "text/html");
  return doc.documentElement.textContent;
};
