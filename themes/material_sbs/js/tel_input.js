import intlTelInput from "intl-tel-input";

window.onload = () => {
  const telInput = document.querySelector("input[type=tel]");
  if (telInput) {
    telInput.value = "";
    telInput.oninput = () => {
      let telInput = document.querySelector("input[type=tel]");
      telInput.value = telInput.value.replace(/[^0-9]/g, "");
    };
    intlTelInput(telInput, {
      utilsScript:
        "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js",
      hiddenInput: "phone-country-code",
    });
  }
};
