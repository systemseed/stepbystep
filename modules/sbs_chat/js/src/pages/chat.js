import React, { useState } from "react";
import { nanoid } from "nanoid";
import {
  ChatContainer,
  MessageList,
  MessageGroup,
  Message,
  MessageSeparator,
  MessageInput,
} from "@chatscope/chat-ui-kit-react";
import {
  ChatProvider,
  useChat,
  MessageContentType,
  MessageDirection,
  MessageStatus,
} from "@chatscope/use-chat";
import { DrupalChatService } from "../utilities/DrupalChatService";
import { ChatStorage } from "../utilities/ChatStorage";
import SnackAlert from "@anu/components/SnackAlert";

const messageIdGenerator = () => nanoid();
const groupIdGenerator = () => nanoid();

const serviceFactory = (storage, updateState) => {
  return new DrupalChatService(storage, updateState);
};

const chatStorage = new ChatStorage({ groupIdGenerator, messageIdGenerator });

const Chat = () => {
  const {
    currentMessages,
    sendMessage,
    currentUser,
    currentMessage,
    setCurrentMessage,
    activeConversation,
  } = useChat();
  const [error, setError] = useState("");

  const handleChange = (value) => {
    setCurrentMessage(value);
  };

  const handleSend = (text) => {
    if (text.length > 700) {
      setError(
        Drupal.t(
          "Your message is too long, please reduce it to less than 700 characters."
        )
      );
      return;
    }
    // Remove unwanted <br> and empty spaces.
    const content = text
      .replaceAll("<br>", "")
      .replaceAll("&nbsp;", " ")
      .trim();

    const message = {
      content,
      contentType: MessageContentType.TextHtml,
      senderId: currentUser.id,
      direction: MessageDirection.Outgoing,
      status: MessageStatus.Sent,
      timestamp: Date.now(),
    };

    sendMessage({
      message,
      conversationId: activeConversation.id,
      senderId: currentUser.id,
    });

    setError("");
  };

  const hasPendingMessages = chatStorage.getPendingMessages().length !== 0;

  return (
    <>
      <SnackAlert
        show={hasPendingMessages}
        message={Drupal.t("Your message hasn't been sent. Retrying...")}
        severity="warning"
        variant="filled"
        spaced
        duration={5000}
      />
      <SnackAlert
        show={!!error}
        message={error}
        onClose={() => setError("")}
        severity="error"
        variant="filled"
        spaced
        duration={2000}
      />
      <ChatContainer>
        {currentMessages.length === 0 ? (
          <MessageList
            loadingMore={hasPendingMessages}
            loadingMorePosition="bottom"
          >
            <MessageList.Content className="cs-message__no-message-wrapper">
              <p>
                <strong>
                  {Drupal.t(
                    "Please wait until our team contacts you before sending a message"
                  )}
                </strong>
              </p>
              <p>
                {Drupal.t(
                  "Here you can talk to your E‑helper and get help with your Step‑by‑Step journey"
                )}
              </p>
            </MessageList.Content>
          </MessageList>
        ) : (
          <MessageList>
            {currentMessages.map((g) => (
              <MessageGroup key={g.id} direction={g.direction}>
                <MessageGroup.Messages>
                  {g.messages.map((m) => (
                    <React.Fragment key={m.id}>
                      {m.isNewDate === true && (
                        <MessageSeparator content={m.date} />
                      )}
                      <Message
                        key={m.id}
                        model={{
                          type: "text",
                          payload: m.content,
                        }}
                      >
                        <Message.Footer
                          sentTime={
                            m.status === MessageStatus.Pending
                              ? Drupal.t("Not sent")
                              : m.time
                          }
                        />
                      </Message>
                    </React.Fragment>
                  ))}
                </MessageGroup.Messages>
              </MessageGroup>
            ))}
          </MessageList>
        )}

        <MessageInput
          value={currentMessage}
          placeholder="Write a message..."
          attachButton={false}
          autoFocus /* eslint-disable-line jsx-a11y/no-autofocus */
          onSend={handleSend}
          onChange={handleChange}
          onPaste={(evt) => {
            evt.preventDefault();
            setCurrentMessage(evt.clipboardData.getData("text"));
          }}
        />
      </ChatContainer>
    </>
  );
};

const ChatPage = () => {
  return (
    <ChatProvider serviceFactory={serviceFactory} storage={chatStorage}>
      <Chat />
    </ChatProvider>
  );
};

export default ChatPage;
