import React, { useState } from "react";
import PropTypes from "prop-types";
import Box from "@material-ui/core/Box";
import Typography from "@material-ui/core/Typography";
import makeStyles from "@material-ui/core/styles/makeStyles";
import { withStyles } from "@material-ui/core";
import AudioPlayer from "@anu/components/Audio/AudioPlayer";
import PageWrapper from "../components/PageWrapper";
import StickyButton from "../components/StickyButton";
import { getDestinationUrl } from "../utilities/getDestinationUrl";

const Player = withStyles((theme) => ({
  time: {
    fontWeight: theme.typography.fontWeightMedium,
  },
}))(AudioPlayer);

const useStyles = makeStyles((theme) => ({
  heading: {
    fontSize: "1.25rem",
    fontWeight: theme.typography.fontWeightBold,
    lineHeight: 1.25,
    marginBottom: theme.spacing(1),
  },
}));

const AudioPage = ({ audioUrl, title, description }) => {
  const classes = useStyles();
  const [isError, setError] = useState(false);

  /**
   * Handles event when player could not load audio file.
   */
  const onAudioLoadError = () => {
    setError(true);
  };

  return (
    <PageWrapper>
      <Box pt={5}>
        <Typography variant="h1" className={classes.heading}>
          {title}
        </Typography>

        {description && <Typography variant="body2">{description}</Typography>}
      </Box>

      <Box m="auto" width={300} pb={"3rem"}>
        <Box py={5}>
          <Player url={audioUrl} onError={onAudioLoadError} />
        </Box>
      </Box>

      <StickyButton text={Drupal.t("Done")} href={getDestinationUrl()} />

      {isError && (
        <Typography variant="caption" color="error">
          {Drupal.t(
            "Error loading the exercise. Please, contact site administrator or your coordinator for help."
          )}
        </Typography>
      )}
    </PageWrapper>
  );
};
AudioPage.propTypes = {
  id: PropTypes.number.isRequired,
  title: PropTypes.string.isRequired,
  description: PropTypes.string,
  audioUrl: PropTypes.string.isRequired,
};

export default AudioPage;
