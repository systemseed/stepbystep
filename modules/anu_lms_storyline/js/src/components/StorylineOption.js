import makeStyles from "@material-ui/core/styles/makeStyles";
import { useRadioGroup } from "@material-ui/core/RadioGroup";
import Box from "@material-ui/core/Box";
import FormControlLabel from "@material-ui/core/FormControlLabel";
import PropTypes from "prop-types";
import React from "react";
import StorylineLabel from "@anu/components/StorylineLabel";
import { Radio } from "@material-ui/core";
import { storylinePropTypes } from "@anu/utilities/transform.storylines";

const useStyles = makeStyles((theme) => ({
  storylineBox: ({ checked }) => ({
    borderColor: checked ? theme.palette.primary.main : theme.palette.grey[300],
    borderWidth: "2px",
    borderStyle: "solid",
    borderRadius: "4px",
    backgroundColor: checked
      ? theme.palette.washed.blue
      : theme.palette.common.white,
    marginTop: theme.spacing(1),
    marginBottom: theme.spacing(1),
  }),
  formControlLabel: {
    margin: 0,
    width: "auto",
  },
}));

const StorylineOption = ({ value, storyline }) => {
  const radioGroup = useRadioGroup();
  let checked = false;
  if (radioGroup) {
    checked = radioGroup.value === value;
  }
  const classes = useStyles({ checked });

  return (
    <>
      <Box py={1} className={classes.storylineBox}>
        <FormControlLabel
          checked={checked}
          value={value}
          label={<StorylineLabel {...storyline} checked={checked} />}
          control={<Radio color="primary" size="small" />}
          className={classes.formControlLabel}
        />
      </Box>
    </>
  );
};

StorylineOption.propTypes = {
  value: PropTypes.any,
  storyline: PropTypes.shape(storylinePropTypes),
};

export default StorylineOption;
