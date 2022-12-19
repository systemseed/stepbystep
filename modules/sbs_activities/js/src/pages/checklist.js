import React, { useState, useEffect, useRef } from "react";
import ContentEditable from "react-contenteditable";
import PropTypes from "prop-types";
import { Detector } from "react-detect-offline";
import { nanoid } from "nanoid";
import Box from "@material-ui/core/Box";
import Link from "@material-ui/core/Link";
import Typography from "@material-ui/core/Typography";
import Checkbox from "@material-ui/core/Checkbox";
import IconButton from "@material-ui/core/IconButton";
import AddIcon from "@material-ui/icons/Add";
import CloseIcon from "@material-ui/icons/Close";
import DeleteIcon from "@material-ui/icons/Delete";
import BulbIcon from "@material-ui/icons/EmojiObjectsOutlined";
import makeStyles from "@material-ui/core/styles/makeStyles";
import LoadingIndicator from "@anu/components/LoadingIndicator";
import useLocalStorage from "../../../../sbs_application/js/src/hooks/useLocalStorage";
import { getUserId } from "@anu/utilities/settings";
import PageWrapper from "../components/PageWrapper";
import StickyButton from "../components/StickyButton";
import { usePrevious, useInterval } from "../utilities/hooks";
import { saveChecklist } from "../api/checklist";
import { getDestinationUrl } from "../utilities/getDestinationUrl";

const useStyles = makeStyles((theme) => ({
  heading: {
    fontSize: "1.25rem",
    fontWeight: theme.typography.fontWeightBold,
    lineHeight: 1.25,
  },
  savingIndicator: {
    marginLeft: theme.spacing(2),
    minWidth: 85,
    "& > div": {
      alignItems: "center",
      justifyContent: "flex-end",
    },
  },
  addItem: {
    display: "flex",
    cursor: "pointer",
    border: "1px dashed " + theme.palette.primary.main,
    borderRadius: "4px",
    padding: theme.spacing(1.5),
    marginBottom: theme.spacing(2),
  },
  addItemIcon: {
    marginRight: theme.spacing(1),
  },
  itemWrapper: {
    display: "flex",
    padding: theme.spacing(0.5, 1, 0.5, 0.5),
    borderRadius: "4px",
  },
  itemWrapperFocused: {
    boxShadow: "0px 1px 4px rgba(68, 89, 99, 0.5)",
  },
  itemWrapperCompleted: {
    background: theme.palette.washed.green,
  },
  itemContent: {
    outline: "none",
    flexGrow: 1,
    margin: theme.spacing(1, 0),
  },
  itemDeleteButton: {
    margin: "auto",
    padding: theme.spacing(1),
    color: theme.palette.grey[400],
  },
  itemCompleteChecklist: {
    margin: "auto",
  },
  itemAddButton: {
    fontWeight: theme.typography.fontWeightBold,
    padding: theme.spacing(1),
    cursor: "pointer",
    margin: "auto 0",
    textDecoration: "underline",
  },
  suggestedItems: {
    paddingTop: theme.spacing(4),
    marginTop: "auto",
  },
  suggestedItemsIcon: {
    color: theme.palette.grey[400],
    marginRight: theme.spacing(0.5),
  },
  suggestedItemWrapper: {
    background: theme.palette.grey[200],
    display: "flex",
    justifyContent: "space-between",
    alignItems: "center",
    padding: theme.spacing(0.5, 0.5, 0.5, 1.5),
    marginTop: theme.spacing(1),
    borderRadius: "4px",
  },
  suggestedItemAddButton: {
    fontWeight: theme.typography.fontWeightBold,
    padding: theme.spacing(1),
    marginLeft: theme.spacing(1),
    cursor: "pointer",
    margin: "auto 0",
    textDecoration: "underline",
  },
}));

const ChecklistPage = ({
  id,
  title,
  initialItems,
  suggestedItems,
  description,
  suggestionsLabel,
}) => {
  const classes = useStyles();

  const [showAddNewItemCTA, setShowAddNewItemCTA] = useState(true);
  const storageKey = `Anu.activityChecklist.u${getUserId()}.activity${id}`;
  const storageItems = window.localStorage.getItem(storageKey);
  const [items, setItems] = useLocalStorage(
    storageKey,
    storageItems ? storageItems : initialItems
  );
  const [needsSave, setNeedsSave] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [filteredSuggestedItems, setSuggestedItems] = useState([]);
  const prevItems = usePrevious(items);
  const newItemRef = useRef(null);

  // Check regularly if there are any changes which have to be saved and
  // sync them if so.
  useInterval(() => {
    if (needsSave && window.navigator.onLine) {
      setNeedsSave(false);
      setIsSaving(true);
      // Filter out items which were not "added" to the checklist explicitly.
      const addedItems = items.filter((item) => !item.isNew);

      // Send API request to the backend to save the current state of
      // the checklist.
      saveChecklist(id, addedItems).then(() => setIsSaving(false));
      // The app doesn't really need backend ids, and the code below is
      // now commented out as it introduces glitches on slow network connection.
      // .then((response) => response.json())
      // .then((savedItems) => {
      //   setIsSaving(false);
      //   setItems((existingItems) => {
      //     // Merge items from the backend with items in the current state.
      //     return existingItems.map((item) => {
      //       const savedItem = savedItems.find(
      //         ({ internalId }) => item.internalId === internalId
      //       );
      //       return savedItem ? { ...item, id: savedItem.id } : item;
      //     });
      //   });
      // });
    }
  }, 3000);

  // Trigger initial synchronization for possible items in localStorage
  // which were not synchronized yet.
  // This will happen only one time on page loading.
  useEffect(() => {
    setNeedsSave(true);
  }, []);

  // Filter out suggested items which were already added to the checklist.
  useEffect(() => {
    filterSuggestedItems(items);
  }, [items]);

  // Prevent user from leaving the page with unsaved changes.
  useEffect(() => {
    const onPageLeave = (event) => {
      if (needsSave && window.navigator.onLine) {
        const message = Drupal.t(
          "Leave the page? Changes you made may not be saved."
        );
        event.preventDefault();
        if (event) {
          event.returnValue = message;
        }
        return message;
      }
    };

    window.addEventListener("beforeunload", onPageLeave);
    return () => {
      window.removeEventListener("beforeunload", onPageLeave);
    };
  });

  // When new item is being added we want to automatically focus
  // on this element.
  useEffect(() => {
    const isItemAdded = items && prevItems && items.length > prevItems.length;
    if (isItemAdded && newItemRef.current) {
      newItemRef.current.focus();
    }
  });

  // A helper to filter out suggested items already added to the checklist.
  const filterSuggestedItems = (items) => {
    const filteredSuggestedItems = [];
    suggestedItems.forEach((suggestedItem) => {
      const itemIsInChecklist =
        items.find((item) => item.text === suggestedItem) !== undefined;
      if (!itemIsInChecklist) {
        filteredSuggestedItems.push(suggestedItem);
      }
    });
    setSuggestedItems(filteredSuggestedItems);
  };

  // Handles click on "Add new" CTA.
  const handleAddNewItem = () => {
    // Add a new item to the list.
    setItems((existingItems) => [
      ...existingItems,
      {
        // ID is indication that the item is saved on the backend.
        // NULL means it is not saved on the backend.
        id: null,
        text: "",
        isCompleted: false,
        isNew: true,
        isFocused: true,
        // Internal ID is needed to be able to match
        // items in the state with the items saved by the backend.
        internalId: nanoid(),
      },
    ]);

    // Hide CTA which lets user add new item to the checklist
    // until the current element is saved.
    setShowAddNewItemCTA(false);
  };

  // Handle click on checkbox element of checklist item.
  const handleCheckboxChange = (index) => {
    setItems((existingItems) => {
      const newItems = [...existingItems];
      newItems[index] = {
        ...newItems[index],
        isCompleted: !newItems[index].isCompleted,
      };
      return newItems;
    });
    setNeedsSave(true);
  };

  // Handle text change in the checklist item.
  const handleTextChange = (event, index) => {
    setItems((existingItems) => {
      const newItems = [...existingItems];
      newItems[index] = {
        ...newItems[index],
        text: event.target.value,
      };
      return newItems;
    });

    // Set flag only if changed item is explicitly added to the checklist.
    if (!items[index].isNew) {
      setNeedsSave(true);
    }
  };

  // Handle click on "Add" button when creating a new checklist item.
  const handleAddNewItemConfirm = (index) => {
    setItems((existingItems) => {
      const newItems = [...existingItems];
      newItems[index] = {
        ...newItems[index],
        isNew: false,
        isFocused: false,
      };
      return newItems;
    });

    // Make sure the item will be saved now.
    setNeedsSave(true);

    // After adding a new item we can show CTA to add another one.
    setShowAddNewItemCTA(true);
  };

  // Handle click on "Cancel" button when creating a new checklist item.
  const handleCancelItemAdding = (index) => {
    setItems((existingItems) => {
      return existingItems.filter((item, i) => i !== index);
    });

    // After cancelling adding a new item we can show CTA to add another one.
    setShowAddNewItemCTA(true);
  };

  // Handle click on "Delete" icon.
  const handleDeleteItem = (index) => {
    setItems((existingItems) => {
      return existingItems.filter((item, i) => i !== index);
    });

    setNeedsSave(true);
  };

  // Handle click on "Add" CTA on suggested item.
  const handleAddSuggestedItem = (text) => {
    // Add a new item to the list.
    setItems((existingItems) => [
      ...existingItems,
      {
        // ID is indication that the item is saved on the backend.
        // NULL means it is not saved on the backend.
        id: null,
        text: text,
        isCompleted: false,
        isNew: false,
        isFocused: false,
        // Internal ID is needed to be able to match
        // items in the state with the items saved by the backend.
        internalId: existingItems.length + 1,
      },
    ]);

    setNeedsSave(true);
  };

  return (
    <PageWrapper>
      <Box pt={5} pb={2}>
        <Box
          mb={1}
          display="flex"
          justifyContent="space-between"
          alignItems="center"
        >
          <Typography variant="h1" className={classes.heading}>
            {title}
          </Typography>

          <Box className={classes.savingIndicator}>
            <Detector
              polling={false}
              render={({ online }) => (
                <>
                  {online ? (
                    <LoadingIndicator
                      isLoading={isSaving}
                      label={
                        isSaving
                          ? Drupal.t("Saving") + "..."
                          : Drupal.t("Saved")
                      }
                    />
                  ) : (
                    <LoadingIndicator label={Drupal.t("Offline")} />
                  )}
                </>
              )}
            />
          </Box>
        </Box>

        {description && <Typography variant="body2">{description}</Typography>}
      </Box>

      {items.map(({ text, isCompleted, isFocused, isNew }, index) => (
        <Box mb={1} key={index}>
          <Box
            className={`
              ${classes.itemWrapper}
              ${isFocused ? classes.itemWrapperFocused : ""}
              ${isCompleted ? classes.itemWrapperCompleted : ""}
            `}
          >
            {isNew ? (
              <IconButton
                className={classes.itemDeleteButton}
                onClick={() => handleCancelItemAdding(index)}
              >
                <CloseIcon color="primary" />
              </IconButton>
            ) : (
              <Checkbox
                color="primary"
                checked={isCompleted}
                className={classes.itemCompleteChecklist}
                onChange={() => handleCheckboxChange(index)}
              />
            )}

            <ContentEditable
              innerRef={isNew ? newItemRef : null}
              className={classes.itemContent}
              html={text}
              onChange={(e) => handleTextChange(e, index)}
            />

            {isNew && (
              <Link
                className={classes.itemAddButton}
                onClick={() => handleAddNewItemConfirm(index)}
              >
                {Drupal.t("Add")}
              </Link>
            )}

            {!isNew && (
              <IconButton
                className={classes.itemDeleteButton}
                onClick={() => handleDeleteItem(index)}
              >
                <DeleteIcon />
              </IconButton>
            )}
          </Box>
        </Box>
      ))}

      {showAddNewItemCTA && (
        <Box
          className={classes.addItem}
          onClick={handleAddNewItem}
          role="button"
          aria-label="Add new"
        >
          <AddIcon className={classes.addItemIcon} color="primary" />
          <Typography variant="body2" color="primary" component="span">
            {Drupal.t("Add new")}
          </Typography>
        </Box>
      )}

      {filteredSuggestedItems.length > 0 && (
        <Box mb={"5rem"} className={classes.suggestedItems}>
          <Box display="flex">
            <BulbIcon className={classes.suggestedItemsIcon} />

            <Typography variant="body2">
              {suggestionsLabel
                ? suggestionsLabel
                : Drupal.t("Our suggestions")}
              :
            </Typography>
          </Box>

          {filteredSuggestedItems.map((item) => (
            <Box key={item} className={classes.suggestedItemWrapper}>
              <Typography variant="body2" component="span">
                {item}
              </Typography>

              <Link
                className={classes.suggestedItemAddButton}
                onClick={() => handleAddSuggestedItem(item)}
              >
                {Drupal.t("Add")}
              </Link>
            </Box>
          ))}
        </Box>
      )}

      <StickyButton
        text={Drupal.t("Save and exit")}
        href={getDestinationUrl()}
      />
    </PageWrapper>
  );
};

ChecklistPage.propTypes = {
  id: PropTypes.number.isRequired,
  title: PropTypes.string.isRequired,
  description: PropTypes.string,
  suggestedItems: PropTypes.arrayOf(PropTypes.string),
  suggestionsLabel: PropTypes.string,
  initialItems: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.string,
      text: PropTypes.string,
      isCompleted: PropTypes.bool,
      isNew: PropTypes.bool,
      isFocused: PropTypes.bool,
      internalId: PropTypes.number,
    })
  ),
};

export default ChecklistPage;
