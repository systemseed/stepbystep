import React from "react";
import ReactDOM from "react-dom";
import Application from "@anu/Application";
import ChatPage from "../pages/chat";

const App = () => (
  <Application>
    <ChatPage />
  </Application>
);

document.addEventListener("DOMContentLoaded", () => {
  const tab = document.querySelector('a[href="#sbs-tab-messages"]');
  const element = document.getElementById("chat");

  // If the chat is rendered in a tab, wait for it to be activated.
  if (tab) {
    tab.addEventListener(
      "click",
      () => {
        ReactDOM.render(<App />, element);
      },
      { once: true }
    );
  } else {
    ReactDOM.render(<App />, element);
  }
});
