import PropTypes from "prop-types";
import * as fields from "@anu/utilities/fields";
import { transformLesson } from "@anu/utilities/transform.lesson";
import { transformQuiz } from "@anu/utilities/transform.quiz";
import {
  transformCourseCategory,
  courseCategoryPropTypes,
} from "@anu/utilities/transform.courseCategory";
import { transformCoursesPage } from "@anu/utilities/transform.courses";
import {
  calculateProgressPercent,
  prepareCourseProgress,
} from "@anu/utilities/progress";
import { removeTags } from "@anu/utilities/removeTags";
import { decodeHTMLEntities } from "@anu/utilities/decodeHTMLEntities";

/**
 * Transform course node from Drupal backend
 * into frontend-friendly object.
 */
const transformCourse = (node, data) => {
  // Make sure the course node data exists.
  if (!fields.getNumberValue(node, "nid")) {
    return null;
  }

  const courseId = fields.getNumberValue(node, "nid");
  // Prepare courses pages for current course.
  let coursesPages = [];
  if (data.courses_pages_by_course) {
    data.courses_pages_by_course.map((set) => {
      if (set.course_id === courseId) {
        coursesPages = set.courses_pages;
      }
    });
  }

  // Prepare first lesson url for current course.
  let firstLessonUrl = "";
  if (data.first_lesson_url_by_course) {
    data.first_lesson_url_by_course.map((set) => {
      if (set.course_id === courseId) {
        firstLessonUrl = set.first_lesson_url;
      }
    });
  }

  const progress = prepareCourseProgress(node);
  const transform = {
    id: courseId,
    title: fields.getTextValue(node, "title"),
    description: decodeHTMLEntities(
      removeTags(fields.getTextValue(node, "field_course_description"))
    ),
    url: fields.getNodeUrl(node),
    image: fields.getImage(node, "field_course_image", "course_preview"),
    categories: fields
      .getArrayValue(node, "field_course_category")
      .map((term) => transformCourseCategory(term)),
    labels: fields
      .getArrayValue(node, "field_course_label")
      .map((term) => fields.getTextValue(term, "name")),
    content: fields
      .getArrayValue(node, "field_course_module")
      .map((module) => ({
        module: fields.getTextValue(module, "field_module_title"),
        lessons: fields
          .getArrayValue(module, "field_module_lessons")
          .map((lesson) => transformLesson(lesson, { id: courseId, progress }))
          .filter((lesson) => !!lesson),
        quiz: transformQuiz(
          fields.getArrayValue(module, "field_module_assessment")[0],
          data
        ),
      })),
    courses_pages: coursesPages.map((coursesPage) =>
      transformCoursesPage({ data: coursesPage })
    ),
    first_lesson_url: firstLessonUrl,
    progress,
    progress_percent: calculateProgressPercent(progress),
    // TODO: add support for offline unlocking of courses in categories.
    isLocked: fields.getBooleanValue(node, "locked"),
    audios: fields.getArrayValue(node, "audios"),
  };

  // If progress is available, point first lesson URL to the first non-restricted lesson.
  if (progress && transform.content.length) {
    for (let i = transform.content.length - 1; i >= 0; i--) {
      const module = transform.content[i];
      for (let j = module.lessons.length - 1; j >= 0; j--) {
        const lesson = module.lessons[j];
        if (!lesson.isRestricted) {
          return {
            ...transform,
            first_lesson_url: lesson.url,
          };
        }
      }
    }
  }

  return transform;
};

/**
 * Define expected prop types for course.
 */
const coursePropTypes = PropTypes.shape({
  id: PropTypes.number.isRequired,
  title: PropTypes.string.isRequired,
  url: PropTypes.string.isRequired,
  description: PropTypes.string,
  image: PropTypes.shape({
    url: PropTypes.string.isRequired,
    alt: PropTypes.string.isRequired,
    type: PropTypes.string.isRequired,
  }),
  categories: PropTypes.arrayOf(courseCategoryPropTypes),
  content: PropTypes.arrayOf(
    PropTypes.shape({
      module: PropTypes.string,
      // TODO: Use lesson's prop type.
      lessons: PropTypes.arrayOf(PropTypes.shape({})),
    })
  ),
  labels: PropTypes.arrayOf(PropTypes.string),
  courses_pages: PropTypes.arrayOf(PropTypes.shape({})),
  first_lesson_url: PropTypes.string,
  progress: PropTypes.shape({}).isRequired,
  progress_percent: PropTypes.number.isRequired,
  locked: PropTypes.bool,
  audios: PropTypes.arrayOf(PropTypes.string),
});

export { transformCourse, coursePropTypes };
