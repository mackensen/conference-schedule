var gulp = require('gulp');
var sass = require('gulp-sass');
var minify = require('gulp-minify');
var watch = require('gulp-watch');

gulp.task('sass', function() {
	gulp.src('assets/scss/*.scss')
		.pipe(watch('assets/scss/*.scss'))
		.pipe(sass({outputStyle:'compressed'}))
		.pipe(gulp.dest('assets/css'));
});

gulp.task('compress', function() {
	gulp.src(['assets/js/admin-post.js','assets/js/conf-schedule.js','assets/js/conf-schedule-single.js'])
		.pipe(watch(['assets/js/admin-post.js','assets/js/conf-schedule.js','assets/js/conf-schedule-single.js']))
		.pipe(minify())
		.pipe(gulp.dest('assets/js'))
});

gulp.task('default', ['sass','compress']);