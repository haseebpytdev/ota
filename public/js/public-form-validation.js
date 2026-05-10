(function () {
  "use strict";

  function debounce(fn, wait) {
    var timer = null;
    return function () {
      var args = arguments;
      var ctx = this;
      window.clearTimeout(timer);
      timer = window.setTimeout(function () {
        fn.apply(ctx, args);
      }, wait);
    };
  }

  function PublicFormValidation(form, options) {
    this.form = form;
    this.options = options || {};
    this.endpoint = this.options.endpoint || "";
    this.csrf = this.options.csrf || "";
    this.fieldSelectors = this.options.fieldSelectors || {};
    this.requiredFields = this.options.requiredFields || [];
    this.pending = {};
    this.validState = {};
    this.submitButton = form.querySelector('[type="submit"]');
    this.globalError = form.querySelector('[data-global-error]');
  }

  PublicFormValidation.prototype.setGlobalError = function (message) {
    if (!this.globalError) return;
    if (message) {
      this.globalError.textContent = message;
      this.globalError.hidden = false;
      return;
    }
    this.globalError.hidden = true;
    this.globalError.textContent = "";
  };

  PublicFormValidation.prototype.fieldErrorNode = function (field) {
    return this.form.querySelector('[data-error-for="' + field + '"]');
  };

  PublicFormValidation.prototype.fieldInputNode = function (field) {
    return this.form.querySelector('[name="' + field + '"]') || this.form.querySelector('[name="' + (this.fieldSelectors[field] || "") + '"]');
  };

  PublicFormValidation.prototype.clearFieldState = function (field) {
    var input = this.fieldInputNode(field);
    var error = this.fieldErrorNode(field);
    if (error) error.textContent = "";
    if (input) {
      input.classList.remove("input-error");
      input.classList.remove("input-valid");
    }
  };

  PublicFormValidation.prototype.applyFieldError = function (field, message) {
    var input = this.fieldInputNode(field);
    var error = this.fieldErrorNode(field);
    if (error) error.textContent = message || "";
    if (input) {
      input.classList.add("input-error");
      input.classList.remove("input-valid");
    }
    this.validState[field] = false;
  };

  PublicFormValidation.prototype.applyFieldValid = function (field) {
    var input = this.fieldInputNode(field);
    if (input) {
      input.classList.remove("input-error");
      input.classList.add("input-valid");
    }
    this.validState[field] = true;
  };

  PublicFormValidation.prototype.serializePayload = function (field) {
    var payload = {
      field: field
    };
    for (var i = 0; i < this.requiredFields.length; i++) {
      var name = this.requiredFields[i];
      var sourceName = this.fieldSelectors[name] || name;
      var input = this.form.querySelector('[name="' + sourceName + '"]');
      payload[name] = input ? input.value : "";
    }
    return payload;
  };

  PublicFormValidation.prototype.validateField = async function (field) {
    if (!this.endpoint) return true;
    var input = this.fieldInputNode(field);
    if (!input) return true;
    this.clearFieldState(field);
    this.setGlobalError("");

    var value = (input.value || "").trim();
    if (!value && this.requiredFields.indexOf(field) !== -1) {
      this.applyFieldError(field, "This field is required.");
      this.updateSubmitState();
      return false;
    }

    this.pending[field] = true;
    this.updateSubmitState();
    try {
      var response = await fetch(this.endpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-TOKEN": this.csrf
        },
        body: JSON.stringify(this.serializePayload(field))
      });
      var json = await response.json();
      if (!response.ok || !json.valid) {
        var messages = (json.errors && json.errors[field]) || ["Invalid value."];
        this.applyFieldError(field, messages[0]);
        return false;
      }
      this.applyFieldValid(field);
      return true;
    } catch (error) {
      this.setGlobalError("Could not validate right now. Please try again.");
      return false;
    } finally {
      this.pending[field] = false;
      this.updateSubmitState();
    }
  };

  PublicFormValidation.prototype.updateSubmitState = function () {
    if (!this.submitButton) return;
    var pendingFields = Object.keys(this.pending).filter(function (key) {
      return !!this.pending[key];
    }, this);
    var allValid = this.requiredFields.every(function (field) {
      return this.validState[field] === true;
    }, this);
    this.submitButton.disabled = pendingFields.length > 0 || !allValid;
  };

  PublicFormValidation.prototype.install = function () {
    var self = this;
    var debounced = {};

    this.requiredFields.forEach(function (field) {
      self.validState[field] = false;
      debounced[field] = debounce(function () {
        self.validateField(field);
      }, 350);
      var input = self.fieldInputNode(field);
      if (!input) return;
      input.addEventListener("input", function () {
        self.clearFieldState(field);
        self.validState[field] = false;
        self.updateSubmitState();
        debounced[field]();
      });
      input.addEventListener("blur", function () {
        self.validateField(field);
      });
    });

    this.form.addEventListener("submit", async function (event) {
      event.preventDefault();
      self.setGlobalError("");
      if (self.submitButton && self.submitButton.dataset.submitting === "1") return;

      var results = await Promise.all(self.requiredFields.map(function (field) {
        return self.validateField(field);
      }));
      var hasError = results.some(function (ok) { return !ok; });
      if (hasError) return;

      if (self.submitButton) {
        self.submitButton.dataset.submitting = "1";
        self.submitButton.disabled = true;
      }
      self.form.submit();
    });

    this.updateSubmitState();
  };

  window.PublicFormValidation = PublicFormValidation;
})();
