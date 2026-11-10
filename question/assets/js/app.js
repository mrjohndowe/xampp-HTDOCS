/**
 * Progressive enhancements for the questionnaire wizard.
 */
(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    ready(function () {
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var errorSummary = document.querySelector('[data-error-summary]');

        if (errorSummary) {
            errorSummary.setAttribute('tabindex', '-1');
            errorSummary.focus({ preventScroll: true });
            errorSummary.scrollIntoView({
                behavior: reduceMotion ? 'auto' : 'smooth',
                block: 'center'
            });
        }

        var form = document.querySelector('[data-questionnaire]');
        if (!form) {
            return;
        }

        form.querySelectorAll('textarea.field').forEach(function (textarea) {
            var resize = function () {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            };

            textarea.addEventListener('input', resize);
            resize();
        });

        // Conditional follow-up questions -------------------------------------

        var conditionalQuestions = Array.prototype.slice.call(
            form.querySelectorAll('[data-show-if-question]')
        );

        function findQuestion(questionId) {
            return Array.prototype.find.call(
                form.querySelectorAll('[data-question-id]'),
                function (question) {
                    return question.getAttribute('data-question-id') === questionId;
                }
            );
        }

        function questionValue(question) {
            var type = question.getAttribute('data-question-type');
            var fields = question.querySelectorAll('input, textarea');

            if (type === 'multiple') {
                return Array.prototype.filter.call(fields, function (field) {
                    return field.checked;
                }).map(function (field) {
                    return field.getAttribute('data-answer-value') || field.value;
                });
            }

            if (type === 'choice') {
                var checkedChoice = Array.prototype.find.call(fields, function (field) {
                    return field.checked;
                });
                return checkedChoice
                    ? checkedChoice.getAttribute('data-answer-value') || checkedChoice.value
                    : null;
            }

            if (type === 'confirm') {
                var checkedConfirm = Array.prototype.find.call(fields, function (field) {
                    return field.checked;
                });
                return checkedConfirm
                    ? checkedConfirm.getAttribute('data-answer-value') === 'true'
                    : null;
            }

            var textField = question.querySelector('input.field, textarea.field');
            return textField ? textField.value.trim() : '';
        }

        function matchesCondition(actual, expectedValues) {
            return expectedValues.some(function (expected) {
                if (typeof actual === 'boolean' || typeof expected === 'boolean') {
                    return actual === expected;
                }

                if (Array.isArray(actual)) {
                    return actual.some(function (item) {
                        return String(item).trim().toLowerCase()
                            === String(expected).trim().toLowerCase();
                    });
                }

                return String(actual == null ? '' : actual).trim().toLowerCase()
                    === String(expected).trim().toLowerCase();
            });
        }

        function setQuestionActive(question, active) {
            question.hidden = !active;
            question.querySelectorAll('input, textarea, select').forEach(function (field) {
                field.disabled = !active;
            });
        }

        function updateConditionalQuestions() {
            conditionalQuestions.forEach(function (question) {
                var source = findQuestion(question.getAttribute('data-show-if-question'));
                var expected = [];

                try {
                    expected = JSON.parse(question.getAttribute('data-show-if-values') || '[]');
                } catch (error) {
                    expected = [];
                }

                var active = !!source && !source.hidden && Array.isArray(expected)
                    && matchesCondition(questionValue(source), expected);

                setQuestionActive(question, active);
            });
        }

        if (conditionalQuestions.length) {
            updateConditionalQuestions();
            form.addEventListener('change', updateConditionalQuestions);
            form.addEventListener('input', updateConditionalQuestions);
            window.addEventListener('pageshow', updateConditionalQuestions);
        }

        var nextButton = form.querySelector('[data-wizard-next]');
        if (!nextButton) {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (event.submitter && event.submitter.value === 'back') {
                return;
            }

            if (form.dataset.submitted === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitted = 'true';
            nextButton.dataset.originalHtml = nextButton.innerHTML;
            nextButton.disabled = true;
            nextButton.textContent = 'Please wait…';
        });

        window.addEventListener('pageshow', function () {
            delete form.dataset.submitted;
            nextButton.disabled = false;

            if (nextButton.dataset.originalHtml) {
                nextButton.innerHTML = nextButton.dataset.originalHtml;
            }
        });
    });
}());
