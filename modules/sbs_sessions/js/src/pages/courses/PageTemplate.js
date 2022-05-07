import React from "react";
import PropTypes from "prop-types";
import Box from "@material-ui/core/Box";
import Typography from "@material-ui/core/Typography";
import makeStyles from "@material-ui/core/styles/makeStyles";
import CoursesSections from "@anu/pages/courses/Sections";
import { courseCategoryPropTypes } from "@anu/utilities/transform.courseCategory";
import { coursePropTypes } from "@anu/utilities/transform.course";

const useStyles = makeStyles((theme) => ({
  header: {
    backgroundColor: theme.palette.washed.green,
    color: theme.palette.primary.main,
    borderBottomRightRadius: "70px",
    padding: theme.spacing(2, 6, 3, 2),
  },
  title: {
    fontWeight: theme.typography.fontWeightBold,
    marginBottom: theme.spacing(1),
    lineHeight: 1.25,
  },
  description: {
    "& > p:first-child": {
      marginTop: 0,
    },
    "& > p:last-child": {
      marginBottom: 0,
    },
  },
}));

const CoursesPageTemplate = ({ pageTitle, description, courses, sections }) => {
  const classes = useStyles();

  return (
    <>
      <Box className={classes.header}>
        <Typography
          variant="h4"
          component="h1"
          color="primary"
          className={classes.title}
        >
          {pageTitle}
        </Typography>
        <Typography
          variant="body2"
          component="div"
          color="primary"
          className={classes.description}
          dangerouslySetInnerHTML={{ __html: description }}
        />
      </Box>

      <Box pt={2}>
        <CoursesSections sections={sections} courses={courses} />
      </Box>
    </>
  );
};

CoursesPageTemplate.propTypes = {
  pageTitle: PropTypes.string.isRequired,
  description: PropTypes.string.isRequired,
  courses: PropTypes.arrayOf(coursePropTypes),
  categories: PropTypes.arrayOf(courseCategoryPropTypes),
  sections: PropTypes.arrayOf(courseCategoryPropTypes),
  filterValue: PropTypes.oneOfType([PropTypes.number, PropTypes.string])
    .isRequired,
};

CoursesPageTemplate.defaultProps = {
  courses: [],
  categories: [],
  sections: [],
};

export default CoursesPageTemplate;
