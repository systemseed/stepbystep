import Box from "@material-ui/core/Box";
import { Grid, Typography } from "@material-ui/core";
import React from "react";
import PropTypes from "prop-types";
import makeStyles from "@material-ui/core/styles/makeStyles";
import { storylinePropTypes } from "@anu/utilities/transform.storylines";

const descriptionLines = 3;
const titleLines = 1;
const lineClamp = (maxLines, lineHeight, fontSize) => {
  const padding = 0.4;
  const calculatedHeight = maxLines * (lineHeight * fontSize) + padding;
  return {
    height: calculatedHeight.toString() + "rem",
    display: "-webkit-box",
    "-webkit-line-clamp": maxLines,
    "-webkit-box-orient": "vertical",
    overflow: "hidden",
    paddingTop: padding.toString() + "rem",
  };
};

const useStyles = makeStyles((theme) => ({
  optionImage: {
    width: theme.spacing(11),
  },
  title: ({ checked }) => ({
    color: checked ? theme.palette.primary.main : theme.palette.common.black,
    fontWeight: theme.typography.fontWeightBold,
    fontSize: "1rem",
    ...lineClamp(titleLines, theme.typography.h2.lineHeight, 1),
  }),
  img: {
    margin: "auto",
    display: "block",
    maxWidth: "100%",
    maxHeight: "100%",
    borderRadius: "4px",
  },
  description: {
    color: theme.palette.common.black,
    ...lineClamp(descriptionLines, theme.typography.caption.lineHeight, 0.875),
  },
}));

const StorylineLabel = ({ checked, description, image, title }) => {
  const classes = useStyles({ checked: checked });
  return (
    <Grid container wrap="nowrap" alignItems="center" spacing={2}>
      <Grid item>
        <Box className={classes.optionImage}>
          <img src={image.url} alt={image.alt} className={classes.img} />
        </Box>
      </Grid>
      <Grid item xs={8}>
        <Typography variant="h2" className={classes.title}>
          {title}
        </Typography>
        <Typography variant="caption" className={classes.description}>
          {description}
        </Typography>
      </Grid>
    </Grid>
  );
};

StorylineLabel.propTypes = { ...storylinePropTypes, checked: PropTypes.bool };

export default StorylineLabel;
