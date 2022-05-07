import React from "react";
import PropTypes from "prop-types";
import Grid from "@material-ui/core/Grid";
import Card from "@material-ui/core/Card";
import CardContent from "@material-ui/core/CardContent";
import CardActionArea from "@material-ui/core/CardActionArea";
import LinearProgress from "@material-ui/core/LinearProgress";
import LockIcon from "@material-ui/icons/Lock";
import Typography from "@material-ui/core/Typography";
import makeStyles from "@material-ui/core/styles/makeStyles";
import CoursesSectionEmpty from "@anu/pages/courses/SectionEmpty";
import { coursePropTypes } from "@anu/utilities/transform.course";
import { Button, Icon } from "@material-ui/core";
import CheckIcon from "@material-ui/icons/CheckCircle";

const commonCardStyle = (theme) => ({
  marginBottom: theme.spacing(2),
  borderRadius: "4px",
});

const lineClamp = (maxLines) => ({
  display: "-webkit-box",
  "-webkit-line-clamp": maxLines,
  "-webkit-box-orient": "vertical",
  overflow: "hidden",
});

const useStyles = makeStyles((theme) => ({
  isStartableCard: {
    ...commonCardStyle(theme),
    backgroundColor: theme.palette.primary.main,
    color: theme.palette.common.white,
    borderBottomLeftRadius: "50px",
  },
  isCompletedCard: {
    ...commonCardStyle(theme),
    backgroundColor: "white",
    borderColor: `${theme.palette.accent2.main}33`,
    borderWidth: "2px",
    borderStyle: "solid",
    color: theme.palette.common.black,
  },
  isLockedCard: {
    ...commonCardStyle(theme),
    backgroundColor: theme.palette.grey[200],
    borderBottomLeftRadius: "36px",
    borderColor: theme.palette.grey[300],
    borderWidth: "1px",
    borderStyle: "solid",
    color: theme.palette.common.black,
    marginLeft: theme.spacing(4),
  },
  session: {
    padding: theme.spacing(2),
  },
  sessionActive: {
    padding: theme.spacing(2, 2, 2, 4),
  },
  title: {
    color: "inherit",
    fontWeight: theme.typography.fontWeightBold,
    lineHeight: 1.25,
    ...lineClamp(2),
  },
  titleCompleted: {
    color: theme.palette.accent2.main,
    fontWeight: theme.typography.fontWeightBold,
    lineHeight: 1.25,
    ...lineClamp(2),
  },
  description: {
    color: "inherit",
    margin: theme.spacing(1, 0),
    ...lineClamp(3),
  },
  button: {
    background: theme.palette.who.light,
    color: theme.palette.common.white,
    padding: theme.spacing(1, 1.5),
    width: "auto",
    borderRadius: "100px",
    textTransform: "none",
    fontSize: "1rem",
    fontWeight: theme.typography.fontWeightBold,
    letterSpacing: "normal",
    "&:hover": {
      background: theme.palette.who.light,
    },
  },
  buttonCompleted: {
    padding: 0,
    width: "auto",
    textTransform: "none",
    fontSize: "0.875rem",
    fontWeight: theme.typography.fontWeightBold,
    letterSpacing: "normal",
    "& .MuiButton-endIcon": {
      margin: theme.spacing(0, 0, 0, 0.5),
    },
    "& .material-icons": {
      fontSize: "1rem",
    },
  },
  iconWrapper: {
    flexShrink: 0,
    flexGrow: 0,
  },
  icon: {
    fontSize: "0.875rem",
    marginRight: theme.spacing(1),
  },
  iconSuccess: {
    fontSize: "1.25rem",
    color: theme.palette.success.main,
    margin: theme.spacing(0, 0.5, 0, -1),
  },
  contentArea: {
    flexGrow: 1,
    marginRight: theme.spacing(1.5),
  },
  progress: {
    marginTop: theme.spacing(1),
    marginBottom: theme.spacing(1),
    height: 8,
    borderRadius: 5,
    backgroundColor: theme.palette.common.white,
    "& .MuiLinearProgress-barColorPrimary": {
      background: "linear-gradient(90deg, #bf87ff 0%, #ff8861 100% )",
    },
  },
  illustration: {
    width: theme.spacing(10),
    flexGrow: 0,
    flexShrink: 0,
    "& > img": {
      display: "block",
      borderRadius: "4px",
      imageRendering: "-webkit-optimize-contrast",
    },
  },
}));

const states = {
  IS_COMPLETED: "isCompleted",
  IS_LOCKED: "isLocked",
  IS_STARTABLE: "isStartable",
  IS_IN_PROGRESS: "isInProgress",
};

const stateFrom = (course) => {
  const isStarted =
    course.progress_percent > 0 && course.progress_percent < 100;

  const isCompleted = course.progress_percent === 100;
  const isLocked = course.isLocked;
  const isInProgress = !isLocked && isStarted;

  let state = states.IS_STARTABLE;
  if (isCompleted) {
    state = states.IS_COMPLETED;
  }
  if (isLocked) {
    state = states.IS_LOCKED;
  }
  if (isInProgress) {
    state = states.IS_IN_PROGRESS;
  }
  return { isCompleted, isLocked, isInProgress, state };
};

const cardClassNameFrom = (classes, state) => {
  const stateToCardClassName = {};
  stateToCardClassName[states.IS_COMPLETED] = classes.isCompletedCard;
  stateToCardClassName[states.IS_LOCKED] = classes.isLockedCard;
  stateToCardClassName[states.IS_STARTABLE] = classes.isStartableCard;
  stateToCardClassName[states.IS_IN_PROGRESS] = classes.isStartableCard;
  return stateToCardClassName[state];
};

const contentCardClassNameFrom = (classes, state) => {
  const stateToCardClassName = {};
  stateToCardClassName[states.IS_COMPLETED] = classes.session;
  stateToCardClassName[states.IS_LOCKED] = classes.session;
  stateToCardClassName[states.IS_STARTABLE] = classes.sessionActive;
  stateToCardClassName[states.IS_IN_PROGRESS] = classes.sessionActive;
  return stateToCardClassName[state];
};

const CoursesSection = ({ courses }) => {
  const classes = useStyles();

  if (courses.length === 0) {
    return <CoursesSectionEmpty />;
  }

  return courses.map((course) => {
    const { isCompleted, isLocked, isInProgress, state } = stateFrom(course);
    return (
      <Card
        elevation={0}
        className={cardClassNameFrom(classes, state)}
        key={course.id}
      >
        <CardActionArea
          disabled={!course.url || isLocked}
          component="a"
          href={course.first_lesson_url ? course.first_lesson_url : course.url}
        >
          <CardContent className={contentCardClassNameFrom(classes, state)}>
            <Grid container wrap="nowrap">
              <Grid className={classes.iconWrapper}>
                {isCompleted && (
                  <CheckIcon fontSize="small" className={classes.iconSuccess} />
                )}
                {isLocked && (
                  <LockIcon fontSize="inherit" className={classes.icon} />
                )}
              </Grid>
              <Grid className={classes.contentArea}>
                <Typography
                  variant="body2"
                  component="h2"
                  className={
                    isCompleted ? classes.titleCompleted : classes.title
                  }
                >
                  {course.title}
                </Typography>
                {isInProgress && (
                  <LinearProgress
                    className={classes.progress}
                    variant="determinate"
                    value={course.progress_percent}
                  />
                )}
                <Typography
                  component="p"
                  variant="caption"
                  className={classes.description}
                >
                  {course.description}
                </Typography>

                {state === states.IS_COMPLETED && (
                  <Button
                    color="primary"
                    endIcon={<Icon>arrow_forward</Icon>}
                    className={classes.buttonCompleted}
                  >
                    {Drupal.t("Review")}
                  </Button>
                )}

                {state === states.IS_IN_PROGRESS && (
                  <Button
                    variant="contained"
                    disableElevation
                    endIcon={<Icon>arrow_forward</Icon>}
                    className={classes.button}
                  >
                    {Drupal.t("Continue")}
                  </Button>
                )}

                {state === states.IS_STARTABLE && (
                  <Button
                    variant="contained"
                    disableElevation
                    endIcon={<Icon>arrow_forward</Icon>}
                    className={classes.button}
                  >
                    {Drupal.t("Get started")}
                  </Button>
                )}
              </Grid>
              <Grid className={classes.illustration}>
                <img src={course.image.url} alt={course.image.alt} />
              </Grid>
            </Grid>
          </CardContent>
        </CardActionArea>
      </Card>
    );
  });
};

CoursesSection.propTypes = {
  courses: PropTypes.arrayOf(coursePropTypes),
};

CoursesSection.defaultProps = {
  courses: [],
};

export default CoursesSection;
