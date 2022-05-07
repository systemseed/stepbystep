import { MessageEvent, MessageStatus } from "@chatscope/use-chat";

// Inspired by @chatscope/use-chat/dist/examples/ExampleChatService.
export class DrupalChatService {
  constructor(storage, update) {
    this.eventHandlers = {
      onMessage: () => {},
      onConnectionStateChanged: () => {},
      onUserConnected: () => {},
      onUserDisconnected: () => {},
      onUserPresenceChanged: () => {},
      onUserTyping: () => {},
    };
    this.storage = storage;
    this.updateState = update;
    // SBS chat is a single conversation app so conversationId is always
    // the same - it's a participant id.
    this.conversationId = null;
    // Store timestamp of previous successful API call. Used to reduce data
    // payload during API polling.
    this.lastCheckTime = Date.now();
    // Drupal CSRF token.
    this.token = null;
    this.tokenExpiration = null;

    const { conversations } = storage.getState();

    if (conversations.length) {
      // The chat is initialised - nothing to do.
      return;
    }

    // Init chat with data from backend.
    if (drupalSettings && drupalSettings.chat) {
      const participants = [];

      // We always have 2 users in chat. A participant and an e-helper.
      // Participant id is always equal to the conversation id.
      participants.push({ id: drupalSettings.chat.conversationId });
      participants.push({ id: "e-helper" });

      participants.forEach((participant) => storage.addUser(participant));
      storage.addConversation(
        {
          id: drupalSettings.chat.conversationId,
        },
        participants
      );
      this.conversationId = drupalSettings.chat.conversationId;

      storage.setCurrentUser({ id: drupalSettings.chat.activeUser });
      storage.setActiveConversation(drupalSettings.chat.conversationId);

      drupalSettings.chat.messages.forEach(({ message, conversationId }) => {
        storage.addMessage(message, conversationId);
      });

      this.updateState();
    }

    // Polling Drupal REST endpoint for new messages every 5 sec.
    // Note that only messages since this.lastCheckTime will be returned.
    setInterval(async () => {
      if (!this.conversationId) {
        return;
      }

      await this.fetchNewMessages();
      await this.sendPendingMessages();
    }, 5000);
  }

  async getToken() {
    if (!this.token || this.tokenExpiration < Date.now()) {
      const response = await fetch(Drupal.url("session/token"));
      this.token = await response.text();
      // Cache token for 2 minutes.
      this.tokenExpiration = Date.now() + 120 * 1000;
    }

    return this.token;
  }

  async fetchNewMessages() {
    try {
      const response = await fetch(
        Drupal.url(
          "chat/" +
            this.conversationId +
            "?last_message_time=" +
            (Math.floor(this.lastCheckTime / 1000) - 30)
        )
      );

      if (!response.ok) {
        return;
      }

      const data = await response.json();
      if (data && data.chat_messages && data.chat_messages.length) {
        data.chat_messages.forEach(({ message, conversationId }) => {
          this.eventHandlers.onMessage(
            new MessageEvent({ message, conversationId })
          );
        });
        this.lastCheckTime = Date.now();
      }
    } catch (e) {
      console.error(e);
    }
  }

  async sendPendingMessages() {
    const messages = this.storage.getPendingMessages();
    for (let mIndex = 0; mIndex < messages.length; mIndex++) {
      await this.sendMessage({
        message: messages[mIndex],
        conversationId: this.conversationId,
      });
    }
  }

  async sendMessage({ message, conversationId }) {
    let status = MessageStatus.Pending;

    try {
      const token = await this.getToken();

      const response = await fetch(
        Drupal.url(`chat/${conversationId}/message`),
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": token,
          },
          body: JSON.stringify(message),
        }
      );

      if (response.ok) {
        status = MessageStatus.Sent;
      }
    } catch (e) {
      console.error(e);
    }

    if (message.status !== status) {
      message.status = status;
      // Update UI if message status has changed.
      this.updateState();
    }

    return message;
  }

  // The ChatProvider registers callbacks with the service.
  // These callbacks are necessary to notify the provider of the changes.
  // For example, when your service receives a message, you need to run an onMessage callback,
  // because the provider must know that the new message arrived.
  // Here you need to implement callback registration in your service.
  // You can do it in any way you like. It's important that you will have access to it elsewhere in the service.
  on(evtType, evtHandler) {
    const key = `on${evtType.charAt(0).toUpperCase()}${evtType.substring(1)}`;
    if (key in this.eventHandlers) {
      this.eventHandlers[key] = evtHandler;
    }
  }
  // The ChatProvider can unregister the callback.
  // In this case remove it from your service to keep it clean.
  off(evtType) {
    const key = `on${evtType.charAt(0).toUpperCase()}${evtType.substring(1)}`;
    if (key in this.eventHandlers) {
      this.eventHandlers[key] = () => {};
    }
  }
}
