import React from "react";
import RadioGroup from "@material-ui/core/RadioGroup";
import { Button } from "@material-ui/core";
import makeStyles from "@material-ui/core/styles/makeStyles";
import StorylineOption from "@anu/components/StorylineOption";
import Box from "@material-ui/core/Box";
import { storylinesPagePropTypes } from "@anu/utilities/transform.storylines";

const useStyles = makeStyles(() => ({
  submitButton: {
    height: "3rem !important",
    letterSpacing: "normal !important",
    width: "100%",
    borderRadius: "4px",
    fontSize: "1rem",
  },
}));

const StorylinesSelector = ({ storylines, currentStoryline }) => {
  const classes = useStyles();
  const firstStoryline = storylines.find((x) => x !== undefined);
  const [selectedStoryline, setSelectedStoryline] = React.useState(
    currentStoryline || firstStoryline.id
  );
  const [token, setToken] = React.useState("");

  const handleChange = (event) => {
    setSelectedStoryline(event.target.value);
  };
  React.useEffect(() => {
    fetch(Drupal.url("session/token"))
      .then((response) => response.text())
      .then((token) => {
        setToken(token);
      });
  }, []);

  return (
    <form action={Drupal.url("select-storyline")} method="post">
      <input type="hidden" name="form_token" value={token} />
      <RadioGroup
        name="storyline-id"
        value={selectedStoryline}
        onChange={handleChange}
      >
        {storylines.map((storyline) => {
          return (
            <StorylineOption
              key={storyline.id}
              storyline={storyline}
              value={storyline.id.toString()}
            />
          );
        })}
      </RadioGroup>
      <Box my={2}>
        <Button
          variant="contained"
          color="primary"
          className={classes.submitButton}
          type="submit"
        >
          {currentStoryline ? Drupal.t("Save") : Drupal.t("Continue")}
        </Button>
      </Box>

      {!currentStoryline && (
        <Box component="p">
          {Drupal.t(
            "You can change the character selection at any point from your profile settings."
          )}
        </Box>
      )}
    </form>
  );
};

StorylinesSelector.propTypes = storylinesPagePropTypes;

export default StorylinesSelector;
