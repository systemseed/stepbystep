import React, { useEffect } from "react";
import PropTypes from "prop-types";
import { Detector } from "react-detect-offline";
import { useHistory } from "react-router-dom";

import Button from "@material-ui/core/Button";
import ChevronRightIcon from "@material-ui/icons/ChevronRight";
import ChevronLeftIcon from "@material-ui/icons/ChevronLeft";
import { Tooltip } from "@material-ui/core";

import LessonGrid from "@anu/components/LessonGrid";
import ButtonWrapper from "@anu/components/ButtonWrapper";

// TODO - isIntro
const ContentNavigation = ({
  isIntro,
  sections,
  currentLesson,
  nextLesson,
  prevLesson,
  currentIndex,
  isEnabled,
  lessonGridMode,
}) => {
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
  const noPrevLesson = !sections[currentIndex - 1];

  if (!currentLesson.finishButtonUrl.length) {
    const language = window.drupalSettings.path.currentLanguage;

    currentLesson.finishButtonUrl =
      (language === "en" ? "" : "/" + language) + "/sessions";
  }

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

  const isFirstSection = !currentIndex;

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

        const renderButtonLabel = (label) => {
          return label;
        };

        const renderButtonWithTooltip = (button) => {
          // "span" is required to display tooltip for disabled buttons.
          return (
            <Tooltip title={disabled ? completeAnswer : ""} arrow>
              <span>{button}</span>
            </Tooltip>
          );
        };

        return (
          <LessonGrid mode={lessonGridMode}>
            <ButtonWrapper>
              {prevLesson &&
                noPrevLesson &&
                !isFirstSection &&
                !nextIsActivity && (
                  <Button
                    variant="outlined"
                    color="primary"
                    size="large"
                    startIcon={<ChevronLeftIcon />}
                    href={prevLesson.url}
                    data-test="anu-lms-navigation-previous"
                  >
                    {renderButtonLabel(
                      Drupal.t("Previous", {}, { context: "ANU LMS" })
                    )}
                  </Button>
                )}

              {!noPrevLesson && !nextIsActivity && (
                <Button
                  variant="outlined"
                  color="primary"
                  size="large"
                  startIcon={<ChevronLeftIcon />}
                  onClick={() =>
                    history.push({ pathname: `/page-${currentIndex}` })
                  }
                  data-test="anu-lms-navigation-previous"
                >
                  {renderButtonLabel(
                    Drupal.t("Previous", {}, { context: "ANU LMS" })
                  )}
                </Button>
              )}

              {isFirstSection && prevLesson && !nextIsActivity && (
                <Button
                  variant="outlined"
                  color="primary"
                  size="large"
                  startIcon={<ChevronLeftIcon />}
                  href={`${prevLesson.url}#back`}
                  data-test="anu-lms-navigation-previous"
                >
                  {renderButtonLabel(
                    Drupal.t("Previous", {}, { context: "ANU LMS" })
                  )}
                </Button>
              )}

              {sections[currentIndex + 1] &&
                renderButtonWithTooltip(
                  <Button
                    {...buttonProps}
                    onClick={() =>
                      history.push({ pathname: `/page-${currentIndex + 2}` })
                    }
                    data-test="anu-lms-navigation-next"
                  >
                    {renderButtonLabel(
                      Drupal.t("Next", {}, { context: "ANU LMS" })
                    )}
                  </Button>
                )}

              {noNextLesson &&
                nextIsLesson &&
                !nextIsActivity &&
                renderButtonWithTooltip(
                  <Button
                    {...buttonProps}
                    onClick={updateProgressAndRedirect}
                    data-test="anu-lms-navigation-next"
                  >
                    {renderButtonLabel(
                      Drupal.t("Next", {}, { context: "ANU LMS" })
                    )}
                  </Button>
                )}

              {noNextLesson &&
                !nextIsLesson &&
                !nextIsQuiz &&
                !nextIsActivity &&
                renderButtonWithTooltip(
                  <Button
                    {...buttonProps}
                    onClick={updateProgressAndRedirect}
                    data-test="anu-lms-navigation-finish"
                  >
                    {renderButtonLabel(
                      Drupal.t("Finish", {}, { context: "ANU LMS" })
                    )}
                  </Button>
                )}

              {noNextLesson && nextIsLesson && isIntro && (
                <Button
                  {...buttonProps}
                  onClick={updateProgressAndRedirect}
                  data-test="anu-lms-navigation-start"
                >
                  {renderButtonLabel(
                    Drupal.t("Start", {}, { context: "ANU LMS" })
                  )}
                </Button>
              )}

              {noNextLesson &&
                nextIsQuiz &&
                !nextIsActivity &&
                renderButtonWithTooltip(
                  <Button
                    {...buttonProps}
                    onClick={updateProgressAndRedirect}
                    data-test="anu-lms-navigation-next"
                  >
                    {renderButtonLabel(
                      Drupal.t("Next", {}, { context: "ANU LMS" })
                    )}
                  </Button>
                )}

              {noNextLesson && nextIsLesson && nextIsActivity && (
                <Button {...buttonProps} onClick={updateProgressAndRedirect}>
                  {disabled
                    ? completeAnswer
                    : Drupal.t("See Activity", {}, { context: "ANU LMS" })}
                </Button>
              )}

              {noNextLesson && !nextIsLesson && !nextIsQuiz && nextIsActivity && (
                <Button {...buttonProps} onClick={updateProgressAndRedirect}>
                  {disabled
                    ? completeAnswer
                    : Drupal.t("See Activity", {}, { context: "ANU LMS" })}
                </Button>
              )}
            </ButtonWrapper>
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
  prevLesson: PropTypes.shape(),
  currentIndex: PropTypes.number,
  isEnabled: PropTypes.bool,
  lessonGridMode: PropTypes.string,
};

export default ContentNavigation;
