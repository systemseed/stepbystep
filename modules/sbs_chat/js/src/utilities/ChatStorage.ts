import {BasicStorage, ChatMessage, ConversationId, MessageContentType, MessageStatus, MessageDirection} from "@chatscope/use-chat";

/**
 * Extends @chatscope storage with extra helpers for SBS chat.
 *
 * This is a TypeScript class because the original class is written in TypeScript.
 */
export class ChatStorage extends BasicStorage {

  /**
   * A helper to get last chat message from the storage.
   */
  getLastMessage(): ChatMessage {
    if (!this.activeConversationId) {
      return null;
    }

    const groups = this.messages[this.activeConversationId];
    if (!groups || !groups.length) {
      return null;
    }
    const lastGroup = groups[groups.length - 1];
    if (!lastGroup.messages || !lastGroup.messages.length) {
      return null;
    }

    return lastGroup.messages[lastGroup.messages.length - 1];
  }

  /**
   * A helper to get all messages with pending status (not sent).
   */
  getPendingMessages(): Array<ChatMessage> {
    const pendingMessages = [];
    if (!this.activeConversationId) {
      return [];
    }
    const groups = this.messages[this.activeConversationId];
    if (!groups || !groups.length) {
      return [];
    }

    for (let gIndex = 0; gIndex < groups.length; gIndex++) {
      const messages = groups[gIndex].messages;
      for (let mIndex = 0; mIndex < messages.length; mIndex++) {
        if (messages[mIndex].status === MessageStatus.Pending && messages[mIndex].direction == MessageDirection.Outgoing) {
          pendingMessages.push(messages[mIndex]);
        }
      }
    }

    return pendingMessages;
  }

  /**
   * Extend addMessage with extra app logic and metadata.
   */
  addMessage(
    originalMessage: ChatMessage<MessageContentType>,
    conversationId: ConversationId,
    generateId = false
  ): ChatMessage<MessageContentType> {

    // Do not add the message if a message with the same id already exists.
    // It can happen when the user sends the message and then receives it back
    // in the next API polling call.
    if (originalMessage.id && this.messages[conversationId]) {
      const groups = this.messages[conversationId];
      for (let gIndex = 0; gIndex < groups.length; gIndex++) {
        const group = groups[gIndex];

        const [currentMessage, mIndex] = group.getMessage(originalMessage.id);
        if (currentMessage) {
          // Mutate this.messages because storage.updateMessage() doesn't work
          // as expected. Updating status only.
          this.messages[conversationId][gIndex].messages[mIndex].status = originalMessage.status;
          return currentMessage;
        }
      }
    }

    // Format sent date & time.
    const date = new Date(originalMessage.timestamp).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
      });
    const time = new Date(originalMessage.timestamp).toLocaleTimeString("en-GB", { hour: '2-digit', minute: '2-digit' });

    // Set isNewDate flag if the message should have a date separator above it.
    let isNewDate = false;
    const prevMessage = this.getLastMessage();
    if (!prevMessage || prevMessage.date !== date) {
      isNewDate = true;
    }

    const message = new ChatMessage({
      ...originalMessage,
      contentType: originalMessage.contentType || MessageContentType.TextHtml,
    });

    // Attach extra props dynamically as TypeScript will ignore any undeclared
    // properties on object initialisation.
    message.date = date;
    message.time = time;
    message.isNewDate = isNewDate;

    return super.addMessage(message, conversationId, generateId);
  }
}
