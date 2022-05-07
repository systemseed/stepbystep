/**
 * Get the destination parameter from the URL.
 */
const getDestinationUrl = () => {
  // Grab destination URL from the current page URL.
  const urlParams = new URLSearchParams(window.location.search);
  // If no destination set then close button will redirect to the home page.
  return urlParams.get("destination") || "/";
};

export { getDestinationUrl };
