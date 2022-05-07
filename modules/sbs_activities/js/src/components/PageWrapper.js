import React, { useState } from "react";
import PropTypes from "prop-types";
import Box from "@material-ui/core/Box";
import Link from "@material-ui/core/Link";
import CloseIcon from "@material-ui/icons/Close";
import makeStyles from "@material-ui/core/styles/makeStyles";
import SnackAlert from "@anu/components/SnackAlert";
import { getDestinationUrl } from "../utilities/getDestinationUrl";

const useStyles = makeStyles(() => ({
  wrapper: {
    display: "flex",
    flexDirection: "column",
    height: "100%",
  },
  closeButton: {
    position: "absolute",
    right: 0,
    top: 0,
    zIndex: 10,
  },
}));

const PageWrapper = ({ children }) => {
  const classes = useStyles();

  // Grab destination URL from the current page URL.
  const urlParams = new URLSearchParams(window.location.search);
  // If no destination set then close button will redirect to the home page.
  const destinationURL = urlParams.get("destination") || "/";
  const [showUnlockedAlert, setShowUnlockedAlert] = useState(
    !!urlParams.get("unlocked") || false
  );

  return (
    <Box
      className={classes.wrapper}
      style={showUnlockedAlert ? { paddingTop: "100px" } : {}}
    >
      <SnackAlert
        show={showUnlockedAlert}
        message="This activity is now unlocked. You can try it now or later from your toolbox at any time."
        onClose={() => setShowUnlockedAlert(false)}
        severity="success"
        duration={5000}
      />

      {destinationURL && (
        <Link
          href={getDestinationUrl()}
          className={classes.closeButton}
          aria-label={Drupal.t("Close")}
        >
          <CloseIcon />
        </Link>
      )}
      {children}
    </Box>
  );
};

PageWrapper.propTypes = {
  children: PropTypes.node.isRequired,
};

export default PageWrapper;
