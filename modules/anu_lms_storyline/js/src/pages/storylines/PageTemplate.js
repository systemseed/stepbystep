import Box from "@material-ui/core/Box";
import Container from "@material-ui/core/Container";
import { Link, Typography } from "@material-ui/core";
import React from "react";
import makeStyles from "@material-ui/core/styles/makeStyles";
import StorylinesSelector from "@anu/components/StorylinesSelector";
import { storylinesPagePropTypes } from "@anu/utilities/transform.storylines";
import { Alert } from "@material-ui/lab";
import { Announcement, Close } from "@material-ui/icons";

const useStyles = makeStyles((theme) => ({
  header: {
    backgroundColor: theme.palette.washed.green,
    color: theme.palette.primary.main,
    borderBottomRightRadius: "70px",
    paddingTop: theme.spacing(4),
    paddingBottom: theme.spacing(4),
    marginBottom: theme.spacing(2),
  },
  title: {
    fontWeight: theme.typography.fontWeightBold,
  },
  subtitle: {
    marginTop: theme.spacing(1.5),
  },
  alert: {
    color: theme.palette.common.black,
    borderRadius: 0,
    marginBottom: theme.spacing(2),
  },
  alertIcon: {
    marginTop: theme.spacing(0.5),
  },
  close: {
    display: "flex",
    alignItems: "center",
    justifyContent: "flex-end",
    margin: theme.spacing(1),
  },
  closeIcon: {
    fontSize: "1.5rem",
  },
}));

const PageTemplate = ({ storylines, currentStoryline }) => {
  const classes = useStyles();
  return (
    <>
      {currentStoryline ? (
        <>
          <Link
            href="#"
            onClick={(e) => {
              e.preventDefault();
              window.history.back();
            }}
            className={classes.close}
          >
            <Close className={classes.closeIcon} />
          </Link>
          <Container>
            <Box py={2}>
              <Typography variant="h5" component="h1">
                {Drupal.t("Change character selection")}
              </Typography>
            </Box>
          </Container>
          <Alert
            severity="warning"
            variant="filled"
            icon={
              <Announcement fontSize="inherit" className={classes.alertIcon} />
            }
            className={classes.alert}
          >
            {Drupal.t(
              "Note that this will start your sessions from the beginning"
            )}
          </Alert>
        </>
      ) : (
        <Box className={classes.header}>
          <Container>
            <Typography
              variant="h4"
              component="h1"
              color="primary"
              className={classes.title}
            >
              {Drupal.t("Let's get started!")}
            </Typography>
            <Box className={classes.subtitle}>
              {Drupal.t(
                "Select the characters from the options below to follow their story."
              )}
            </Box>
          </Container>
        </Box>
      )}
      <Container>
        <StorylinesSelector
          storylines={storylines}
          currentStoryline={currentStoryline}
        />
      </Container>
    </>
  );
};

PageTemplate.propTypes = storylinesPagePropTypes;

export default PageTemplate;
