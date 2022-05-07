import React from "react";
import PropTypes from "prop-types";
import Box from "@material-ui/core/Box";
import Button from "@material-ui/core/Button";
import makeStyles from "@material-ui/core/styles/makeStyles";

const useStyles = makeStyles((theme) => ({
  sticky: {
    position: "fixed",
    bottom: 0,
    width: "100%",
    textAlign: "center",
    marginLeft: "-16px",
    borderTop: "1px solid",
    backgroundColor: "#fff",
    borderColor: theme.palette.primary.main,
    [theme.breakpoints.up("md")]: {
      width: theme.breakpoints.values.md,
    },
  },
  button: {
    margin: "20px auto",
    width: "96%",
    borderColor: theme.palette.primary.main,
    textTransform: "none",
    color: theme.palette.primary.main,
    lineHeight: "2em",
  },
}));

const StickyButton = ({ text, href }) => {
  const classes = useStyles();

  return (
    <Box className={classes.sticky}>
      <Button className={classes.button} variant="outlined" href={href}>
        {text}
      </Button>
    </Box>
  );
};

StickyButton.propTypes = {
  text: PropTypes.string,
  href: PropTypes.string,
};

export default StickyButton;
