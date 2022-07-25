import React, { useEffect } from "react";
import PropTypes from "prop-types";
import { Detector } from "react-detect-offline";
import { useHistory } from "react-router-dom";

import makeStyles from "@material-ui/core/styles/makeStyles";
import Button from "@material-ui/core/Button";
import ChevronRightIcon from "@material-ui/icons/ChevronRight";

import LessonGrid from "@anu/components/LessonGrid";

const useStyles = makeStyles(() => ({
  activityButton: {
    height: "3rem !important",
    letterSpacing: "normal !important",
    width: "100%",
    borderRadius: "4px",
    fontSize: "1rem",
  },
}));

// TODO - isIntro
const ContentNavigation = ({
  isIntro,
  sections,
  currentLesson,
  nextLesson,
  currentIndex,
  isEnabled,
}) => {
  const classes = useStyles();
  const history = useHistory();
  const completeAnswer = Drupal.t(
    "Complete all answers to proceed",
    {},
    { context: "ANU LMS" }
  );
  const nextIsQuiz = nextLesson && Boolean(nextLesson.questions);
  const nextIsLesson = nextLesson && Boolean(nextLesson.sections);
  const nextIsActivity = Boolean(currentLesson.upcomingActivity);
  const noNextLesson = !sections[currentIndex + 1];

  const updateProgressAndRedirect = async () => {
    // Marks lesson as completed if linear progress is enabled for its course.
    await currentLesson.complete();

    // SBS customisations start.
    if (noNextLesson && nextIsLesson && nextIsActivity) {
      window.location.href = `${
        currentLesson.upcomingActivity.url
      }?destination=${nextLesson.url}${
        currentLesson.isCompleted ? "" : "&unlocked=1"
      }`;
      return;
    }
    if (noNextLesson && !nextIsLesson && !nextIsQuiz && nextIsActivity) {
      window.location.href = `${
        currentLesson.upcomingActivity.url
      }?destination=${new URL(currentLesson.finishButtonUrl).pathname}`;
      return;
    }
    // SBS customisations end.

    // Redirect to the next page.
    if (noNextLesson && !nextIsLesson && !nextIsQuiz) {
      window.location.href = currentLesson.finishButtonUrl;
      return;
    }

    window.location.href = nextLesson.url;
  };

  const finishButtonText = (currentLesson) =>
    !currentLesson.finishButtonText
      ? Drupal.t("Finish", {}, { context: "ANU LMS" })
      : currentLesson.finishButtonText;

  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <Detector
      polling={false}
      render={({ online }) => {
        const disabled = !online ? false : !isEnabled;
        const buttonProps = {
          variant: "contained",
          color: "primary",
          endIcon: <ChevronRightIcon />,
          disabled,
        };
        const activityButtonProps = {
          variant: "contained",
          color: "primary",
          className: classes.activityButton,
          disabled,
        };

        return (
          <LessonGrid>
            {sections[currentIndex + 1] && (
              <Button
                {...buttonProps}
                onClick={() =>
                  history.push({ pathname: `/section-${currentIndex + 2}` })
                }
              >
                {disabled
                  ? completeAnswer
                  : Drupal.t("Next", {}, { context: "ANU LMS" })}
              </Button>
            )}

            {noNextLesson && nextIsLesson && nextIsActivity && (
              <Button
                {...activityButtonProps}
                onClick={updateProgressAndRedirect}
              >
                {disabled
                  ? completeAnswer
                  : Drupal.t("See Activity", {}, { context: "ANU LMS" })}
              </Button>
            )}

            {noNextLesson && nextIsLesson && !nextIsActivity && (
              <Button {...buttonProps} onClick={updateProgressAndRedirect}>
                {disabled
                  ? completeAnswer
                  : Drupal.t("Next", {}, { context: "ANU LMS" })}
              </Button>
            )}

            {noNextLesson && !nextIsLesson && !nextIsQuiz && nextIsActivity && (
              <Button
                {...activityButtonProps}
                onClick={updateProgressAndRedirect}
              >
                {disabled
                  ? completeAnswer
                  : Drupal.t("See Activity", {}, { context: "ANU LMS" })}
              </Button>
            )}

            {noNextLesson && !nextIsLesson && !nextIsQuiz && !nextIsActivity && (
              <Button {...buttonProps} onClick={updateProgressAndRedirect}>
                {disabled ? completeAnswer : finishButtonText(currentLesson)}
              </Button>
            )}

            {noNextLesson && nextIsLesson && isIntro && (
              <Button {...buttonProps} onClick={updateProgressAndRedirect}>
                {Drupal.t("Start", {}, { context: "ANU LMS" })}
              </Button>
            )}

            {noNextLesson && nextIsQuiz && (
              <Button {...buttonProps} onClick={updateProgressAndRedirect}>
                {disabled
                  ? completeAnswer
                  : Drupal.t("Go to quiz", {}, { context: "ANU LMS" })}
              </Button>
            )}
          </LessonGrid>
        );
      }}
    />
  );
};

ContentNavigation.propTypes = {
  isIntro: PropTypes.bool,
  sections: PropTypes.arrayOf(PropTypes.arrayOf(PropTypes.shape())),
  currentLesson: PropTypes.shape(),
  nextLesson: PropTypes.shape(),
  currentIndex: PropTypes.number,
  isEnabled: PropTypes.bool,
};

export default ContentNavigation;
