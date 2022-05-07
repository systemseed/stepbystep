document
  .querySelector(".back-navigation")
  .addEventListener("click", (event) => {
    event.preventDefault();
    window.history.back();
  });
