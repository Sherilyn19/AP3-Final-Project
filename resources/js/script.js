/**
 * ============================================================================
 * \resources\js\script.js
 * ============================================================================
 */

/**
 * Toggle Password Visibility
 * Switches between showing and hiding password text
 * Works with any password field by finding the closest parent and toggling the input
 * ***********Used in: Login, Registration Forms**************
 */

window.togglePassword = function(buttonElement) {
    // Find the password input - it's a sibling in the same parent div
    const container = buttonElement.closest('div');
    const passwordInput = container.querySelector('input[type="password"], input[type="text"]');
    const eyeIcon = buttonElement.querySelector('svg');
    
    if (!passwordInput || !eyeIcon) return; // Safety check
    
    // Toggle between 'password' and 'text' input types
    if (passwordInput.type === 'password') {
        // Show password
        passwordInput.type = 'text';
        
        // Change icon to "eye-off" (crossed eye)
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
        `;
    } else {
        // Hide password
        passwordInput.type = 'password';
        
        // Change icon back to regular "eye"
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        `;
    }
}

/**
 * Form Validation Enhancement
 * Adds real-time validation feedback
 */
document.addEventListener('DOMContentLoaded', function() {

    // Password toggle buttons
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            togglePassword(this);
        });
    });
    
    // Get all input fields
    const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    
    // Add validation on blur (when user leaves field)
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        // Remove error styling on input
        input.addEventListener('input', function() {
            if (this.classList.contains('border-red-500')) {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-300');
            }
        });
    });
    
    /**
     * Validate individual field
     * @param {HTMLElement} field - The input field to validate
     */
    function validateField(field) {
        // Check if field is empty and required
        if (field.hasAttribute('required') && !field.value.trim()) {
            field.classList.add('border-red-500');
            field.classList.remove('border-gray-300');
            return false;
        }
        
        // Email validation
        if (field.type === 'email' && field.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                field.classList.add('border-red-500');
                field.classList.remove('border-gray-300');
                return false;
            }
        }
        
        // Phone validation (11 digits for Philippines)
        if ((field.name === 'phone' || field.name === 'emergency_contact_phone' || field.name === 'parent_guardian_phone') && field.value) {
            const phoneRegex = /^[0-9]{11}$/;
            if (!phoneRegex.test(field.value)) {
                field.classList.add('border-red-500');
                field.classList.remove('border-gray-300');
                return false;
            }
        }
        
        // Field is valid
        field.classList.remove('border-red-500');
        field.classList.add('border-gray-300');
        return true;
    }
    
    /**
     * Password Confirmation Match Check
     * For registration forms
     */
    const passwordConfirm = document.getElementById('user_password_confirmation');
    const password = document.getElementById('user_password');
    
    if (passwordConfirm && password) {
        passwordConfirm.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.classList.add('border-red-500');
                this.classList.remove('border-gray-300');
            } else {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-300');
            }
        });
    }
    
    /**
     * Phone Number Formatting
     * Only allow numbers, limit to 11 digits
     */
    const phoneInputs = document.querySelectorAll('input[name="phone"], input[name="emergency_contact_phone"], input[name="parent_guardian_phone"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            // Remove any non-numeric characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 11 digits
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });
    });
    
    /**
     * Auto-dismiss Success/Error Messages
     * After 5 seconds
     */
    const alerts = document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000); // 5 seconds
    });
    
    /**
     * Specialization Checkbox Handler (Instructor Registration)
     * Ensure at least one specialization is selected
     */
    const specializationCheckboxes = document.querySelectorAll('input[name="specializations[]"]');
    const primarySpecializationSelect = document.querySelector('select[name="primary_specialization"]');
    
    if (specializationCheckboxes.length > 0 && primarySpecializationSelect) {
        specializationCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updatePrimarySpecializationOptions();
            });
        });
        
        function updatePrimarySpecializationOptions() {
            const checkedValues = Array.from(specializationCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            const options = primarySpecializationSelect.querySelectorAll('option');
            options.forEach(option => {
                if (option.value && !checkedValues.includes(option.value)) {
                    option.disabled = true;
                } else if (option.value) {
                    option.disabled = false;
                }
            });
            
            if (primarySpecializationSelect.value && !checkedValues.includes(primarySpecializationSelect.value)) {
                primarySpecializationSelect.value = '';
            }
        }
    }

    // General loading state for other forms (login, etc.)
    document.querySelectorAll('form:not(#instructorForm):not(#studentForm)').forEach(form => {
        form.addEventListener('submit', function () {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = `
                    <svg class="animate-spin h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                `;
            }
        });
    });

        // === REUSABLE MULTI-STEP REGISTRATION FORM ===
    const registrationConfig = getRegistrationConfig();
    const registrationSteps = getRegistrationSteps();
    const blurOverlay = document.getElementById('blurOverlay');
    const motivationText = document.getElementById('motivationText');

    function getRegistrationConfig() {
        if (document.getElementById('instructorForm')) {
            return {
                formId: 'instructorForm',
                motivations: [
                    "Almost there! Let's complete your emergency contact...",
                    "Great progress! Just one more step to showcase your expertise...",
                ],
            };
        }

        if (document.getElementById('studentForm')) {
            return {
                formId: 'studentForm',
                motivations: [
                    "Great! Now let's add your guardian and emergency contact...",
                    "Almost done! Time to share your musical background...",
                ],
            };
        }

        return null;
    }

    function getRegistrationSteps() {
        return {
            panels: [
                document.getElementById('step1'),
                document.getElementById('step2'),
                document.getElementById('step3'),
            ],
            indicators: Array.from(document.querySelectorAll('.step-item')),
            buttons: {
                nextFromStepOne: document.getElementById('nextStepBtn1'),
                nextFromStepTwo: document.getElementById('nextStepBtn2'),
                previousFromStepTwo: document.getElementById('prevStepBtn2'),
                previousFromStepThree: document.getElementById('prevStepBtn3'),
            },
        };
    }

    function validateStep(step) {
        if (!step) {
            return false;
        }

        const existingError = step.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        const fields = step.querySelectorAll('input[required], select[required], textarea[required]');
        const emptyFields = [];

        fields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500', 'animate-shake');
                setTimeout(() => field.classList.remove('animate-shake'), 500);

                const label = field.closest('div')?.querySelector('label');
                emptyFields.push(label ? label.textContent.replace(' *', '').trim() : field.name);
            } else {
                field.classList.remove('border-red-500');
            }
        });

        if (emptyFields.length > 0) {
            showStepError(step, emptyFields);
            return false;
        }

        return true;
    }

    function showStepError(step, emptyFields) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = `
            <strong>Please fill in all required fields:</strong>
            <ul class="list-disc list-inside mt-2">
                ${emptyFields.map(field => `<li>${field}</li>`).join('')}
            </ul>
        `;

        const grid = step.querySelector('.grid');

        if (grid) {
            grid.insertAdjacentElement('afterend', errorDiv);
        } else {
            step.appendChild(errorDiv);
        }

        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function showMotivation(index) {
        if (!registrationConfig || !blurOverlay || !motivationText) {
            return;
        }

        motivationText.textContent = registrationConfig.motivations[index] || '';
        blurOverlay.classList.add('active');

        setTimeout(() => {
            blurOverlay.classList.remove('active');
        }, 2000);
    }

    function moveToStep(currentIndex, targetIndex, motivationIndex = null) {
        const currentStep = registrationSteps.panels[currentIndex];
        const targetStep = registrationSteps.panels[targetIndex];

        if (!currentStep || !targetStep) {
            return;
        }

        if (motivationIndex !== null) {
            showMotivation(motivationIndex);
        }

        setTimeout(() => {
            currentStep.classList.remove('active', 'swipe-right', 'swipe-right-back');
            currentStep.classList.add(targetIndex > currentIndex ? 'swipe-left' : 'swipe-right-back');

            targetStep.classList.remove('hidden', 'swipe-left', 'swipe-right-back');
            targetStep.classList.add('active', 'swipe-right');

            updateStepIndicators(targetIndex);
        }, motivationIndex !== null ? 500 : 0);
    }

    function updateStepIndicators(activeIndex) {
        registrationSteps.indicators.forEach((item, index) => {
            item.classList.toggle('active', index <= activeIndex);
        });
    }

    if (registrationConfig && registrationSteps.panels.every(Boolean)) {
        const buttons = registrationSteps.buttons;

        buttons.nextFromStepOne?.addEventListener('click', () => {
            if (!validateStep(registrationSteps.panels[0])) {
                return;
            }

            moveToStep(0, 1, 0);
        });

        buttons.nextFromStepTwo?.addEventListener('click', () => {
            if (!validateStep(registrationSteps.panels[1])) {
                return;
            }

            moveToStep(1, 2, 1);
        });

        buttons.previousFromStepTwo?.addEventListener('click', () => {
            moveToStep(1, 0);
        });

        buttons.previousFromStepThree?.addEventListener('click', () => {
            moveToStep(2, 1);
        });
    }

    // Age calculation
    const dobInput = document.getElementById('date_of_birth');
    const ageDisplay = document.getElementById('age-display');
    if (dobInput && ageDisplay) {
        dobInput.addEventListener('change', function () {
            const birth = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
            ageDisplay.textContent = age >= 0 ? age + ' years old' : '—';
        });
    }

    // Capitalize names/address/languages
    document.querySelectorAll('.capitalize-words').forEach(el => {
        el.addEventListener('input', function () {
            this.value = this.value.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
        });
    });

    // Email lowercase
    const emailInput = document.querySelector('.lowercase-email');
    if (emailInput) {
        emailInput.addEventListener('input', function () {
            this.value = this.value.toLowerCase();
        });
    }

    // Dynamic certifications (Instructor)
    const certContainer = document.getElementById('certifications-container');
    const addCertBtn = document.getElementById('add-cert-btn');

    if (addCertBtn && certContainer) {
        addCertBtn.addEventListener('click', () => {
            const div = document.createElement('div');
            div.className = 'flex gap-4 mb-4';
            div.innerHTML = `
                <input type="text" name="certifications[]" class="input-field flex-1" placeholder="Certificate name">
                <button type="button" class="text-red-600 hover:text-red-800 font-medium">Remove</button>
            `;
            certContainer.appendChild(div);
            div.querySelector('button').addEventListener('click', () => div.remove());
        });
    }

    // Dynamic secondary instruments (Student)
    const secondaryInstrContainer = document.getElementById('secondary-instruments-container');
    const addInstrumentBtn = document.getElementById('add-instrument-btn');

    if (addInstrumentBtn && secondaryInstrContainer) {
        addInstrumentBtn.addEventListener('click', () => {
            const div = document.createElement('div');
            div.className = 'flex gap-4 mb-4';
            div.innerHTML = `
                <input type="text" name="secondary_instruments[]" class="input-field flex-1 text-sm capitalize-words" placeholder="Instrument name">
                <button type="button" class="text-red-600 hover:text-red-800 font-medium text-sm">Remove</button>
            `;
            secondaryInstrContainer.appendChild(div);
            div.querySelector('button').addEventListener('click', () => div.remove());
        });
    }

    // Lowercase parent/guardian email
    const parentGuardianEmail = document.querySelector('input[name="parent_guardian_email"]');
    if (parentGuardianEmail) {
        parentGuardianEmail.addEventListener('input', function() {
            this.value = this.value.toLowerCase();
        });
    }

    // === FINAL FORM SUBMISSION (Instructor Registration) ===
    const instructorForm = document.getElementById('instructorForm');
    if (instructorForm) {
        instructorForm.addEventListener('submit', function (e) {
            document.querySelectorAll('#certifications-container > div').forEach(div => {
                const input = div.querySelector('input');
                if (input && !input.value.trim()) div.remove();
            });

            const checkedSpecs = document.querySelectorAll('input[name="specializations[]"]:checked');
            if (checkedSpecs.length === 0) {
                alert('Please select at least one specialization.');
                e.preventDefault();
                return;
            }

            const primarySpec = document.querySelector('select[name="primary_specialization"]');
            if (primarySpec && !primarySpec.value) {
                alert('Please select your primary specialization.');
                e.preventDefault();
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = `
                    <svg class="animate-spin h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                `;
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 10000);
            }
        });
    }

    // === STUDENT REGISTRATION DRAFT + FINAL FORM SUBMISSION ===
    const STUDENT_REGISTRATION_DRAFT_KEY = 'musiclab.student.registration.draft.v1';
    const STUDENT_REGISTRATION_CLEAR_COOKIE = 'musiclab_clear_student_registration_draft';
    const studentForm = document.getElementById('studentForm');

    /*
    |--------------------------------------------------------------------------
    | Student Registration Storage Helpers
    |--------------------------------------------------------------------------
    |
    | These helpers are intentionally small and reusable:
    | - removeStorageItem() safely removes localStorage data
    | - hasCookie() checks if Laravel marked the account creation as successful
    | - deleteCookie() removes the temporary cookie after localStorage is cleared
    |
    */
    function removeStorageItem(key) {
        try {
            localStorage.removeItem(key);
        } catch (error) {
            console.warn('Unable to remove stored registration draft:', error);
        }
    }

    function hasCookie(cookieName) {
        return document.cookie
            .split(';')
            .some(cookie => cookie.trim().startsWith(cookieName + '='));
    }

    function deleteCookie(cookieName) {
        document.cookie = cookieName + '=; Max-Age=0; path=/; SameSite=Lax';
    }

    /*
    |--------------------------------------------------------------------------
    | Clear Draft Only After Successful Create Account & Login
    |--------------------------------------------------------------------------
    |
    | This does not run just because the user reaches /create-account.
    | It only runs when Laravel sends the success cookie after account creation.
    |
    */
    function clearStudentRegistrationDraftAfterSuccessfulAccountCreation() {
        if (!hasCookie(STUDENT_REGISTRATION_CLEAR_COOKIE)) {
            return;
        }

        removeStorageItem(STUDENT_REGISTRATION_DRAFT_KEY);
        deleteCookie(STUDENT_REGISTRATION_CLEAR_COOKIE);
    }

    clearStudentRegistrationDraftAfterSuccessfulAccountCreation();


    if (studentForm) {
        const studentRegistrationDraft = createStudentRegistrationDraftManager(studentForm);
        const guardianSync = createGuardianEmergencySync(studentForm, studentRegistrationDraft);

        studentRegistrationDraft.restoreDraft();
        guardianSync.initialize();
        studentRegistrationDraft.bindAutoSave();

        studentForm.addEventListener('submit', function (e) {
            document.querySelectorAll('#secondary-instruments-container > div').forEach(div => {
                const input = div.querySelector('input');

                if (input && !input.value.trim()) {
                    div.remove();
                }
            });

            guardianSync.syncIfSameGuardian();
            studentRegistrationDraft.saveDraftNow();

            const requiredFields = [
                'first_name',
                'last_name',
                'phone',
                'user_email',
                'address_line1',
                'city',
                'province',
                'postal_code',
                'country',
                'date_of_birth',
                'gender',
                'parent_guardian_name',
                'parent_guardian_relationship',
                'parent_guardian_phone',
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_phone',
                'instrument_id',
                'skill_level',
            ];

            const missingField = requiredFields.find(fieldName => {
                const field = studentForm.querySelector(`[name="${fieldName}"]`);
                return field && !field.value.trim();
            });

            if (missingField) {
                alert(`Please fill in: ${missingField.replace(/_/g, ' ')}`);
                e.preventDefault();
                return;
            }

            setSubmitLoadingState(this.querySelector('button[type="submit"]'));
        });
    }

    function createStudentRegistrationDraftManager(form) {
        const draftStatus = document.getElementById('studentDraftStatus');
        const ignoredFieldTypes = new Set(['password', 'file', 'submit', 'button', 'hidden']);
        let saveTimer = null;

        function getDraftableFields() {
            return Array.from(form.elements).filter(field => {
                return field.name && !ignoredFieldTypes.has(field.type);
            });
        }

        function collectDraftData() {
            const fields = {};

            getDraftableFields().forEach(field => {
                if (field.type === 'radio') {
                    if (field.checked) {
                        fields[field.name] = field.value;
                    }

                    return;
                }

                if (field.type === 'checkbox') {
                    fields[field.name] = field.checked;
                    return;
                }

                if (field.name.endsWith('[]')) {
                    fields[field.name] = fields[field.name] || [];
                    fields[field.name].push(field.value);
                    return;
                }

                fields[field.name] = field.value;
            });

            return {
                updatedAt: new Date().toISOString(),
                fields,
            };
        }

        function readDraft() {
            try {
                const rawDraft = localStorage.getItem(STUDENT_REGISTRATION_DRAFT_KEY);
                return rawDraft ? JSON.parse(rawDraft) : null;
            } catch (error) {
                console.warn('Unable to read student registration draft:', error);
                return null;
            }
        }

        function saveDraftNow() {
            try {
                localStorage.setItem(STUDENT_REGISTRATION_DRAFT_KEY, JSON.stringify(collectDraftData()));
                updateStatus('Progress saved on this device.');
            } catch (error) {
                console.warn('Unable to save student registration draft:', error);
                updateStatus('Auto-save is not available in this browser session.');
            }
        }

        function saveDraftSoon() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(saveDraftNow, 250);
        }

        function restoreDraft() {
            const draft = readDraft();

            if (!draft?.fields) {
                return;
            }

            getDraftableFields().forEach(field => {
                if (!Object.prototype.hasOwnProperty.call(draft.fields, field.name)) {
                    return;
                }

                const savedValue = draft.fields[field.name];

                if (field.type === 'radio') {
                    field.checked = field.value === savedValue;
                    return;
                }

                if (field.type === 'checkbox') {
                    field.checked = Boolean(savedValue);
                    return;
                }

                if (field.name.endsWith('[]')) {
                    return;
                }

                field.value = savedValue;
            });

            updateStatus('Saved draft restored. You can continue your registration.');
        }

        function bindAutoSave() {
            form.addEventListener('input', saveDraftSoon);
            form.addEventListener('change', saveDraftSoon);
            window.addEventListener('beforeunload', saveDraftNow);
        }

        function updateStatus(message) {
            if (draftStatus) {
                draftStatus.textContent = message;
            }
        }

        return {
            bindAutoSave,
            restoreDraft,
            saveDraftNow,
            saveDraftSoon,
        };
    }

    function createGuardianEmergencySync(form, draftManager) {
        const guardianFields = {
            name: form.querySelector('[name="parent_guardian_name"]'),
            relationship: form.querySelector('[name="parent_guardian_relationship"]'),
            phone: form.querySelector('[name="parent_guardian_phone"]'),
        };

        const emergencyFields = {
            name: form.querySelector('[name="emergency_contact_name"]'),
            relationship: form.querySelector('[name="emergency_contact_relationship"]'),
            phone: form.querySelector('[name="emergency_contact_phone"]'),
        };

        const sameGuardianRadios = form.querySelectorAll('[name="emergency_same_as_guardian"]');
        const sameGuardianHint = document.getElementById('sameGuardianHint');

        function initialize() {
            sameGuardianRadios.forEach(radio => {
                radio.addEventListener('change', handleSameGuardianChange);
            });

            Object.values(guardianFields).forEach(field => {
                field?.addEventListener('input', () => {
                    syncIfSameGuardian();
                    draftManager.saveDraftSoon();
                });
            });

            handleSameGuardianChange();
        }

        function isSameGuardianSelected() {
            const selected = form.querySelector('[name="emergency_same_as_guardian"]:checked');
            return selected?.value === 'yes';
        }

        function copyGuardianToEmergency() {
            if (emergencyFields.name) {
                emergencyFields.name.value = guardianFields.name?.value || '';
            }

            if (emergencyFields.relationship) {
                emergencyFields.relationship.value = guardianFields.relationship?.value || '';
            }

            if (emergencyFields.phone) {
                emergencyFields.phone.value = guardianFields.phone?.value || '';
            }
        }

        function setEmergencyReadonlyState(isReadonly) {
            Object.values(emergencyFields).forEach(field => {
                if (!field) {
                    return;
                }

                /*
                 * readonly is used instead of disabled so Laravel still receives
                 * the copied emergency contact values during form submission.
                 */
                field.readOnly = isReadonly;
                field.classList.toggle('bg-gray-100', isReadonly);
                field.classList.toggle('cursor-not-allowed', isReadonly);
            });

            sameGuardianHint?.classList.toggle('hidden', !isReadonly);
        }

        function syncIfSameGuardian() {
            if (isSameGuardianSelected()) {
                copyGuardianToEmergency();
            }
        }

        function handleSameGuardianChange() {
            const shouldCopy = isSameGuardianSelected();

            if (shouldCopy) {
                copyGuardianToEmergency();
            }

            setEmergencyReadonlyState(shouldCopy);
            draftManager.saveDraftSoon();
        }

        return {
            initialize,
            syncIfSameGuardian,
        };
    }

    function setSubmitLoadingState(submitBtn) {
        if (!submitBtn) {
            return;
        }

        submitBtn.disabled = true;

        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        `;

        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }, 10000);
    }

}); // End of DOMContentLoaded

/**
 * Smooth Scroll Behavior
 * For anchor links
 */
document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

/**
 * Keyboard Navigation Enhancement
 * Allow Enter key to submit forms
 */
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        const form = e.target.closest('form');
        if (form && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
        }
    }
});

/**
 * Console Welcome Message
 * For developers
 */
console.log('%cMusic Lab Management System', 'font-size: 20px; font-weight: bold; color: #272829;');
console.log('%cDeveloped with Laravel & Tailwind CSS', 'font-size: 12px; color: #61677A;');

/**
 * Export functions for use in inline scripts if needed
 */
if (typeof window !== 'undefined') {
    window.musicLab = {
        togglePassword,
    };
}


window.openAccountModal = function() {
    document.getElementById('account-modal').classList.remove('hidden');
    document.getElementById('account-modal').classList.add('flex');
}

window.closeAccountModal = function() {
    document.getElementById('account-modal').classList.add('hidden');
    document.getElementById('account-modal').classList.remove('flex');
    document.getElementById('password-form').reset();
}

/**
 * ============================================================================
 * CHANGE PASSWORD FUNCTION - FIXED VERSION
 * Replace the existing window.changePassword function (around line 680-730)
 * ============================================================================
 */

window.changePassword = async function(e) {
    e.preventDefault();
    e.stopPropagation(); // Prevent form from submitting traditionally
    
    const currentPass = document.getElementById('current-password').value;
    const newPass = document.getElementById('new-password').value;
    const confirmPass = document.getElementById('confirm-password').value;
    
    // Client-side validation
    if (newPass !== confirmPass) {
        alert('New password and confirm password do not match');
        return false;
    }
    
    if (newPass.length < 8) {
        alert('New password must be at least 8 characters');
        return false;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    try {
        const response = await fetch('/admin/change-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                current_password: currentPass,
                password: newPass 
            })
        });
        
        const data = await response.json();
        
        if (!response.ok || !data.success) {
            alert(data.message || 'Failed to change password');
            return false;
        }
        
        alert('Password changed successfully!');
        closeAccountModal();
        window.location.reload();
        
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
    
    return false; // Prevent any default form submission
}