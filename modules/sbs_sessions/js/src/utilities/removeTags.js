export const removeTags = (string) => {
  if (string === null || string === "") return "";
  else string = string.toString();

  // Regular expression to identify HTML tags in
  // the input string. Replacing the identified
  // HTML tag with an empty string.
  return string.replace(/(<([^>]+)>)/gi, "");
};
