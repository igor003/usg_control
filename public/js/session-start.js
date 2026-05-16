$(function () {
    const $main = $('main[data-save-session-url]');

    if ($main.length === 0) {
        return;
    }

    let initialSessionData = null;

    try {
        initialSessionData = $main.attr('data-initial-session')
            ? JSON.parse($main.attr('data-initial-session'))
            : null;
    } catch (error) {
        initialSessionData = null;
    }

    const isEditMode = $main.data('is-edit-mode').toString() === '1';
    const sessionState = {
        patient: null,
        selectedOrgans: new Map(),
        isIncognito: false,
        formValues: {},
        isHydratingInitialSession: false,
        editSessionId: initialSessionData && initialSessionData.id ? Number(initialSessionData.id) : null,
    };

    const $patientBlock = $('#sesion_user_info');
    const $idnpInput = $('#session_idnp');
    const $idnpMessage = $('[data-idnp-message]');
    const $patientNameInput = $('#session_patient_name');
    const $patientNameMessage = $('[data-patient-name-message]');
    const $patientNameResults = $('[data-patient-name-results]');
    const $addPatientLink = $('[data-add-patient-link]');
    const $patientSummary = $('[data-patient-summary]');
    const $incognitoPatient = $('[data-patient-incognito]');
    const $imageSection = $('#image_section_image');
    const $organCards = $('.organ-card');
    const $formActions = $('[data-form-actions]');
    const $showExaminationFormButton = $('[data-show-examination-form]');
    const $examinationForm = $('[data-examination-form]');
    const $examinationFormContent = $('[data-examination-form-content]');
    const $ultrasoundTypeSelect = $('[data-ultrasound-type-select]');
    const $selectedContainer = $('[data-selected-organs]');
    const $selectedCount = $('[data-selected-count]');
    const saveSessionUrl = $main.data('save-session-url');
    const sessionsIndexUrl = $main.data('sessions-index-url');
    const patientSearchUrl = $patientBlock.data('patient-search-url');
    const patientNameSearchUrl = $patientBlock.data('patient-name-search-url');
    const newPatientUrl = $patientBlock.data('new-patient-url');
    const patientFields = {
        fullName: $('[data-patient-field="fullName"]'),
        gender: $('[data-patient-field="gender"]'),
        birthYear: $('[data-patient-field="birthYear"]'),
        phone: $('[data-patient-field="phone"]'),
        idnp: $('[data-patient-field="idnp"]'),
        seria: $('[data-patient-field="seria"]'),
        district: $('[data-patient-field="district"]'),
        city: $('[data-patient-field="city"]'),
        address: $('[data-patient-field="address"]'),
        beneficiary: $('[data-patient-field="beneficiary"]'),
    };
    let idnpRequestId = 0;
    let patientNameRequestId = 0;
    let patientNameSearchTimer = null;

    function hasPatientAccess() {
        if (sessionState.isIncognito) {
            return true;
        }

        if (sessionState.patient === null) {
            return false;
        }

        return $patientNameInput.length === 0 || $.trim($patientNameInput.val()) !== '';
    }

    function hideExaminationForm() {
        $examinationForm.prop('hidden', true);
        $examinationFormContent.empty();
    }

    function setStartButtonDisabled(isDisabled) {
        $showExaminationFormButton
            .toggleClass('disabled', isDisabled)
            .prop('disabled', isDisabled)
            .attr('aria-disabled', isDisabled ? 'true' : 'false');
    }

    function renderSelectedOrgans() {
        $selectedContainer.empty();
        $selectedCount.text(sessionState.selectedOrgans.size.toString());

        if (sessionState.selectedOrgans.size === 0) {
            $('<span>', {
                class: 'text-secondary',
                text: 'Niciun organ selectat.',
            }).appendTo($selectedContainer);

            return;
        }

        sessionState.selectedOrgans.forEach(function (organ) {
            $('<span>', {
                class: 'badge text-bg-primary',
                text: organ.name,
            }).appendTo($selectedContainer);
        });
    }

    function getActivePatientGender() {
        if (sessionState.isIncognito || sessionState.patient === null) {
            return null;
        }

        return sessionState.patient.genderCode || null;
    }

    function organMatchesPatientGender(genderApplicability) {
        const patientGender = getActivePatientGender();

        if (patientGender === null || genderApplicability === 'any') {
            return true;
        }

        return genderApplicability === patientGender;
    }

    function persistCurrentFormValues() {
        if ($examinationForm.prop('hidden')) {
            return;
        }

        const $currentForm = $examinationFormContent.find('form').first();

        if ($currentForm.length === 0) {
            return;
        }

        $currentForm.find('input, select, textarea').each(function () {
            const $field = $(this);
            const fieldKey = $field.attr('name') || $field.attr('id');

            if (!fieldKey) {
                return;
            }

            sessionState.formValues[fieldKey] = $field.val();
        });
    }

    function refreshOrganAvailability() {
        if (!hasPatientAccess()) {
            $organCards
                .removeClass('is-gender-disabled')
                .prop('disabled', true)
                .attr('aria-disabled', 'true');

            return;
        }

        const incompatibleSelectedIds = [];

        $organCards.each(function () {
            const $card = $(this);
            const genderApplicability = ($card.data('organ-gender-applicability') || 'any').toString();
            const isAvailable = organMatchesPatientGender(genderApplicability);
            const id = $card.data('organ-id').toString();

            $card
                .toggleClass('is-gender-disabled', !isAvailable)
                .prop('disabled', !isAvailable)
                .attr('aria-disabled', !isAvailable ? 'true' : 'false');

            if (!isAvailable && sessionState.selectedOrgans.has(id)) {
                incompatibleSelectedIds.push(id);
            }
        });

        incompatibleSelectedIds.forEach(function (id) {
            sessionState.selectedOrgans.delete(id);
            $organCards
                .filter(`[data-organ-id="${id}"]`)
                .removeClass('is-active')
                .attr('aria-pressed', 'false');
        });

        if (incompatibleSelectedIds.length > 0) {
            renderSelectedOrgans();
        }

        if (incompatibleSelectedIds.length > 0 && !$examinationForm.prop('hidden')) {
            hideExaminationForm();
        }
    }

    function setOrgansDisabled(isDisabled) {
        $organCards
            .toggleClass('disabled-image-block', isDisabled)
            .prop('disabled', isDisabled)
            .attr('aria-disabled', isDisabled ? 'true' : 'false');
        $imageSection.toggleClass('organ-selection-disabled', isDisabled);

        if (isDisabled) {
            $imageSection.removeClass('focus-style');
        }
    }

    function clearSelectedOrgans(resetFormValues = true) {
        sessionState.selectedOrgans.clear();
        $organCards.removeClass('is-active').attr('aria-pressed', 'false');

        if (resetFormValues) {
            sessionState.formValues = {};
        }

        renderSelectedOrgans();
        hideExaminationForm();
    }

    function setPatientFocusState(isPatientFocused, focusTarget) {
        $patientBlock.toggleClass('focus-style', isPatientFocused);
        $imageSection.toggleClass('focus-style', !isPatientFocused);

        if (isPatientFocused) {
            const $focusElement = focusTarget === 'name' && $patientNameInput.length > 0
                ? $patientNameInput
                : $idnpInput;

            $focusElement.trigger('focus');
        } else {
            $idnpInput.trigger('blur');
        }
    }

    function focusPatientSelection() {
        const focusTarget = $patientNameInput.length > 0 && $.trim($patientNameInput.val()) === '' ? 'name' : 'idnp';

        setPatientFocusState(true, focusTarget);
    }

    function updateInitialState() {
        setPatientFocusState(true);
        setStartButtonDisabled(true);
        setOrgansDisabled(true);
    }

    function updateFormActions() {
        const canShowForm = hasPatientAccess() && sessionState.selectedOrgans.size > 0;

        $formActions.prop('hidden', !canShowForm);
        setStartButtonDisabled(!canShowForm);

        if (!canShowForm) {
            hideExaminationForm();
        }
    }

    function createSafeId(value) {
        return value.toString().replace(/[^a-zA-Z0-9_-]/g, '_');
    }

    function buildParameterFieldId(organId, parameterId, sideKey) {
        return createSafeId(`organ_${organId}${sideKey ? `_${sideKey}` : ''}_parameter_${parameterId}`);
    }

    function buildNoteFieldId(organId, sideKey) {
        return createSafeId(`organ_${organId}${sideKey ? `_${sideKey}` : ''}_note`);
    }

    function readOrganParametersFromCard($card) {
        try {
            return JSON.parse($card.attr('data-organ-parameters') || '[]');
        } catch (error) {
            return [];
        }
    }

    function createOrganStateFromCard($card) {
        return {
            id: $card.data('organ-id').toString(),
            name: $card.data('organ-name'),
            paired: $card.data('organ-paired').toString() === '1',
            genderApplicability: ($card.data('organ-gender-applicability') || 'any').toString(),
            imagePath: $card.data('organ-image-path'),
            parameters: readOrganParametersFromCard($card),
        };
    }

    function setSelectedOrgansByIds(organIds) {
        sessionState.selectedOrgans.clear();
        $organCards.removeClass('is-active').attr('aria-pressed', 'false');

        organIds.forEach(function (organId) {
            const $card = $organCards.filter(`[data-organ-id="${organId}"]`).first();

            if ($card.length === 0 || $card.hasClass('is-gender-disabled') || $card.prop('disabled')) {
                return;
            }

            const organState = createOrganStateFromCard($card);

            $card.addClass('is-active').attr('aria-pressed', 'true');
            sessionState.selectedOrgans.set(organState.id, organState);
        });

        renderSelectedOrgans();
        updateFormActions();
    }

    function getOrganSides(organ) {
        if (!organ.paired) {
            return [null];
        }

        return [
            { key: 'right', label: 'Dreapta' },
            { key: 'left', label: 'Stânga' },
        ];
    }

    function createControlField(organ, parameter, side) {
        const sideKey = side ? side.key : null;
        const fieldId = buildParameterFieldId(organ.id, parameter.id, sideKey);
        const $fieldWrapper = $('<div>', { class: 'mb-3' });

        $('<label>', {
            class: 'form-label',
            for: fieldId,
            text: parameter.name || '-',
        }).appendTo($fieldWrapper);

        if (parameter.valueType === 'select') {
            const options = Array.isArray(parameter.valueContent) ? parameter.valueContent : [];
            const $select = $('<select>', {
                class: 'form-select',
                id: fieldId,
                name: fieldId,
            });

            $('<option>', {
                value: '',
                text: options.length === 0 ? 'Nu există valori definite' : 'Alegeți valoarea',
            }).appendTo($select);

            if (options.length === 0) {
                $select.prop('disabled', true);
            } else {
                $.each(options, function (_, value) {
                    $('<option>', {
                        value,
                        text: value,
                    }).appendTo($select);
                });
            }

            return $fieldWrapper.append($select);
        }

        return $fieldWrapper.append($('<input>', {
            class: 'form-control',
            id: fieldId,
            name: fieldId,
            type: 'text',
        }));
    }

    function createNoteField(organ, side) {
        const noteId = buildNoteFieldId(organ.id, side ? side.key : null);
        const $noteWrapper = $('<div>', { class: 'organ-note' });

        $('<label>', {
            class: 'form-label',
            for: noteId,
            text: side ? `Notă - ${side.label}` : 'Notă',
        }).appendTo($noteWrapper);

        $('<textarea>', {
            class: 'form-control',
            id: noteId,
            name: noteId,
            rows: 3,
        }).appendTo($noteWrapper);

        return $noteWrapper;
    }

    function collectSessionPayload($form) {
        const formElement = $form.get(0);
        const organs = [];

        Array.from(sessionState.selectedOrgans.values()).forEach(function (organ, organIndex) {
            getOrganSides(organ).forEach(function (side) {
                const sideKey = side ? side.key : null;
                const noteId = buildNoteFieldId(organ.id, sideKey);
                const parameters = organ.parameters.map(function (parameter, parameterIndex) {
                    const fieldId = buildParameterFieldId(organ.id, parameter.id, sideKey);

                    return {
                        parameterId: Number(parameter.id),
                        value: formElement.elements[fieldId] ? formElement.elements[fieldId].value : '',
                        sortOrder: Number(parameter.sortOrder || parameterIndex + 1),
                    };
                });

                organs.push({
                    organId: Number(organ.id),
                    side: sideKey || 'single',
                    note: formElement.elements[noteId] ? formElement.elements[noteId].value : '',
                    sortOrder: organIndex + 1,
                    parameters,
                });
            });
        });

        return {
            sessionId: sessionState.editSessionId,
            patientId: sessionState.isIncognito ? null : Number(sessionState.patient && sessionState.patient.id ? sessionState.patient.id : 0),
            ultrasoundTypeId: $ultrasoundTypeSelect.length > 0 && $ultrasoundTypeSelect.val() !== '' ? Number($ultrasoundTypeSelect.val()) : null,
            isIncognito: sessionState.isIncognito,
            sessionNote: formElement.elements.session_note ? formElement.elements.session_note.value : '',
            sessionConclusion: formElement.elements.session_conclusion ? formElement.elements.session_conclusion.value : '',
            organs,
        };
    }

    function saveExaminationForm($form, $message, $button) {
        if (!saveSessionUrl) {
            $message.attr('class', 'alert alert-danger mb-0').text('Ruta de salvare nu este configurată.').prop('hidden', false);
            return;
        }

        $button.prop('disabled', true);
        $message.prop('hidden', true);

        $.ajax({
            url: saveSessionUrl,
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify(collectSessionPayload($form)),
        }).done(function (data) {
            if (data.success) {
                if (data.printUrl) {
                    window.open(data.printUrl, '_blank');
                }

                window.location.href = data.redirectUrl || sessionsIndexUrl;
                return;
            }

            $message
                .attr('class', 'alert alert-danger mb-0')
                .text(data.message || 'Salvarea formularului a eșuat.')
                .prop('hidden', false);
        }).fail(function () {
            $message
                .attr('class', 'alert alert-danger mb-0')
                .text('Salvarea formularului a eșuat.')
                .prop('hidden', false);
        }).always(function () {
            $button.prop('disabled', false);
        });
    }

    function applyStoredFormValues($form) {
        $form.find('input, select, textarea').each(function () {
            const $field = $(this);
            const fieldKey = $field.attr('name') || $field.attr('id');

            if (!fieldKey || !Object.prototype.hasOwnProperty.call(sessionState.formValues, fieldKey)) {
                return;
            }

            $field.val(sessionState.formValues[fieldKey] ?? '');
        });
    }

    function renderExaminationForm() {
        persistCurrentFormValues();
        $examinationFormContent.empty();
        $imageSection.removeClass('focus-style');

        const $form = $('<form>', { novalidate: 'novalidate' });
        const $grid = $('<div>', { class: 'examination-form-grid' });

        sessionState.selectedOrgans.forEach(function (organ) {
            const $organBlock = $('<section>', { class: 'organ-form-block' });
            const $heading = $('<div>', { class: 'organ-form-heading' });

            if (organ.imagePath) {
                $('<img>', {
                    class: 'organ-form-icon',
                    src: organ.imagePath,
                    alt: organ.name,
                }).appendTo($heading);
            } else {
                $('<span>', {
                    class: 'organ-form-icon-placeholder',
                    text: organ.name.slice(0, 1).toUpperCase(),
                }).appendTo($heading);
            }

            $('<h3>', {
                class: 'organ-form-title',
                text: organ.name,
            }).appendTo($heading);

            $organBlock.append($heading);

            if (organ.paired) {
                getOrganSides(organ).forEach(function (side) {
                    const $parameterContainer = $('<div>', { class: 'paired-parameter-group' });

                    $('<h4>', {
                        class: 'h6 mb-3 text-secondary',
                        text: side.label,
                    }).appendTo($parameterContainer);

                    if (organ.parameters.length === 0) {
                        $('<p>', {
                            class: 'mb-3 text-secondary',
                            text: 'Nu există controale asociate.',
                        }).appendTo($parameterContainer);
                    } else {
                        $.each(organ.parameters, function (_, parameter) {
                            $parameterContainer.append(createControlField(organ, parameter, side));
                        });
                    }

                    $parameterContainer.append(createNoteField(organ, side));
                    $organBlock.append($parameterContainer);
                });
            } else {
                if (organ.parameters.length === 0) {
                    $('<p>', {
                        class: 'mb-0 text-secondary',
                        text: 'Nu există controale asociate.',
                    }).appendTo($organBlock);
                } else {
                    $.each(organ.parameters, function (_, parameter) {
                        $organBlock.append(createControlField(organ, parameter, null));
                    });
                }

                $organBlock.append(createNoteField(organ, null));
            }

            $grid.append($organBlock);
        });

        $form.append($grid);

        const $sessionSummary = $('<section>', { class: 'session-form-summary' });

        $('<h3>', {
            class: 'h6 mb-3',
            text: 'Date sesiune',
        }).appendTo($sessionSummary);

        const $sessionNoteWrapper = $('<div>', { class: 'mb-3' });
        $('<label>', {
            class: 'form-label',
            for: 'session_note',
            text: 'Notă',
        }).appendTo($sessionNoteWrapper);
        $('<textarea>', {
            class: 'form-control',
            id: 'session_note',
            name: 'session_note',
            rows: 3,
        }).appendTo($sessionNoteWrapper);
        $sessionSummary.append($sessionNoteWrapper);

        const $conclusionWrapper = $('<div>');
        $('<label>', {
            class: 'form-label',
            for: 'session_conclusion',
            text: 'Concluzie',
        }).appendTo($conclusionWrapper);
        $('<textarea>', {
            class: 'form-control',
            id: 'session_conclusion',
            name: 'session_conclusion',
            rows: 4,
        }).appendTo($conclusionWrapper);
        $sessionSummary.append($conclusionWrapper);

        $form.append($sessionSummary);

        const $saveActions = $('<div>', {
            class: 'd-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-end gap-3 mt-4',
        });
        const $saveMessage = $('<div>', { hidden: true });
        const $saveButton = $('<button>', {
            class: 'btn btn-primary',
            type: 'button',
            text: isEditMode ? 'Actualizează formularul' : 'Salvare formular',
        });

        $saveButton.on('click', function () {
            saveExaminationForm($form, $saveMessage, $saveButton);
        });

        $saveActions.append($saveMessage, $saveButton);
        $form.append($saveActions);

        $examinationFormContent.append($form);
        applyStoredFormValues($form);
        $examinationForm.prop('hidden', false);
        $examinationForm.get(0).scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function setIdnpMessage(text, state) {
        $idnpMessage.text(text).removeClass('text-success text-danger text-secondary').addClass(state);
    }

    function setPatientNameMessage(text, state) {
        $patientNameMessage.text(text).removeClass('text-success text-danger text-secondary').addClass(state);
    }

    function hidePatientNameResults() {
        $patientNameResults.empty().prop('hidden', true);
    }

    function renderPatient(patient) {
        patientFields.fullName.text(`${((patient.lastName || '') + ' ' + (patient.firstName || '')).trim() || '-'}`);
        patientFields.gender.text(patient.gender || '-');
        patientFields.birthYear.text(patient.birthYear || '-');
        patientFields.phone.text(patient.phone || '-');
        patientFields.idnp.text(patient.idnp || '-');
        patientFields.seria.text(patient.seria || '-');
        patientFields.district.text(patient.district || '-');
        patientFields.city.text(patient.city || '-');
        patientFields.address.text(patient.address || '-');
        patientFields.beneficiary.text(patient.beneficiary || 'Nu');
        $patientSummary.prop('hidden', false);
    }

    function getPatientFullName(patient) {
        return `${patient.lastName || ''} ${patient.firstName || ''}`.trim();
    }

    function resetPatient(shouldFocusPatient = false) {
        sessionState.patient = null;
        $patientSummary.prop('hidden', true);
        $addPatientLink.prop('hidden', true);
        hidePatientNameResults();

        $.each(patientFields, function (_, $field) {
            $field.text('');
        });

        setOrgansDisabled(!hasPatientAccess());

        if (!hasPatientAccess()) {
            clearSelectedOrgans();

            if (shouldFocusPatient) {
                focusPatientSelection();
            }
        }

        refreshOrganAvailability();
        updateFormActions();
    }

    function selectPatient(patient, options = {}) {
        const suppressTypeSync = options.suppressTypeSync === true;

        sessionState.patient = patient;
        $addPatientLink.prop('hidden', true);
        $idnpInput.val(patient.idnp || '').removeClass('is-invalid').toggleClass('is-valid', Boolean(patient.idnp));
        $patientNameInput.val(getPatientFullName(patient)).removeClass('is-invalid').addClass('is-valid');

        setIdnpMessage('Pacient selectat.', 'text-success');
        setPatientNameMessage('Pacient selectat.', 'text-success');
        hidePatientNameResults();
        renderPatient(patient);
        setPatientFocusState(false);
        setOrgansDisabled(false);
        refreshOrganAvailability();

        if (!suppressTypeSync && !sessionState.isHydratingInitialSession) {
            $ultrasoundTypeSelect.trigger('change');
        }

        updateFormActions();
    }

    function renderPatientNameResults(patients) {
        $patientNameResults.empty();

        $.each(patients, function (_, patient) {
            const $resultButton = $('<button>', {
                class: 'list-group-item list-group-item-action patient-search-result',
                type: 'button',
            });

            $('<strong>', {
                text: getPatientFullName(patient) || '-',
            }).appendTo($resultButton);

            $('<small>', {
                class: 'text-secondary',
                text: [
                    patient.idnp ? `IDNP: ${patient.idnp}` : null,
                    patient.phone ? `Tel: ${patient.phone}` : null,
                    patient.city || null,
                ].filter(Boolean).join(' · '),
            }).appendTo($resultButton);

            $resultButton.on('click', function () {
                selectPatient(patient);
            });

            $patientNameResults.append($resultButton);
        });

        $patientNameResults.prop('hidden', patients.length === 0);
    }

    function applyUltrasoundTypeSelection() {
        if ($ultrasoundTypeSelect.length === 0) {
            return;
        }

        persistCurrentFormValues();

        let organIds = [];

        try {
            organIds = JSON.parse($ultrasoundTypeSelect.find('option:selected').attr('data-organ-ids') || '[]');
        } catch (error) {
            organIds = [];
        }

        setSelectedOrgansByIds(organIds.map((id) => id.toString()));

        if (!$examinationForm.prop('hidden')) {
            if (sessionState.selectedOrgans.size === 0) {
                hideExaminationForm();
            } else {
                renderExaminationForm();
            }
        }
    }

    function buildFormValuesFromSession(sessionData) {
        const values = {
            session_note: sessionData && sessionData.sessionNote ? sessionData.sessionNote : '',
            session_conclusion: sessionData && sessionData.sessionConclusion ? sessionData.sessionConclusion : '',
        };
        const sessionOrgans = sessionData && Array.isArray(sessionData.organs) ? sessionData.organs : [];

        sessionOrgans.forEach(function (sessionOrgan) {
            const organId = Number(sessionOrgan.organId || 0);
            const sideKey = sessionOrgan.side && sessionOrgan.side !== 'single' ? sessionOrgan.side : null;

            if (organId <= 0) {
                return;
            }

            values[buildNoteFieldId(organId, sideKey)] = sessionOrgan.note || '';

            if (!Array.isArray(sessionOrgan.parameters)) {
                return;
            }

            sessionOrgan.parameters.forEach(function (parameter) {
                const parameterId = Number(parameter.parameterId || 0);

                if (parameterId <= 0) {
                    return;
                }

                values[buildParameterFieldId(organId, parameterId, sideKey)] = parameter.value || '';
            });
        });

        return values;
    }

    function hydrateInitialSession() {
        if (!initialSessionData) {
            renderSelectedOrgans();
            refreshOrganAvailability();
            updateFormActions();
            return;
        }

        sessionState.isHydratingInitialSession = true;
        sessionState.formValues = buildFormValuesFromSession(initialSessionData);

        if ($ultrasoundTypeSelect.length > 0 && initialSessionData.ultrasoundTypeId) {
            $ultrasoundTypeSelect.val(initialSessionData.ultrasoundTypeId.toString());
        }

        if (initialSessionData.isIncognito) {
            $incognitoPatient.prop('checked', true);
            sessionState.isIncognito = true;
            setPatientFocusState(false);
            setOrgansDisabled(false);
        } else if (initialSessionData.patient) {
            selectPatient(initialSessionData.patient, { suppressTypeSync: true });
        }

        setSelectedOrgansByIds(
            Array.isArray(initialSessionData.selectedOrganIds)
                ? initialSessionData.selectedOrganIds.map((id) => id.toString())
                : []
        );

        sessionState.isHydratingInitialSession = false;

        if (sessionState.selectedOrgans.size > 0) {
            renderExaminationForm();
        }
    }

    $incognitoPatient.on('change', function () {
        const isChecked = $(this).is(':checked');
        sessionState.isIncognito = isChecked;

        if (isChecked) {
            setPatientFocusState(false);
            setOrgansDisabled(false);
            refreshOrganAvailability();

            if (!sessionState.isHydratingInitialSession) {
                $ultrasoundTypeSelect.trigger('change');
            }

            updateFormActions();
            return;
        }

        setPatientFocusState(true);
        setStartButtonDisabled(true);
        setOrgansDisabled(true);
        clearSelectedOrgans();
        updateFormActions();
    });

    $showExaminationFormButton.on('click', function () {
        if (!hasPatientAccess() || sessionState.selectedOrgans.size === 0) {
            focusPatientSelection();
            setOrgansDisabled(true);
            clearSelectedOrgans();
            updateFormActions();
            return;
        }

        renderExaminationForm();
    });

    $idnpInput.on('input', function () {
        const sanitizedIdnp = $idnpInput.val().replace(/\D/g, '').slice(0, 13);
        const currentRequest = ++idnpRequestId;

        $idnpInput.val(sanitizedIdnp);
        resetPatient();
        $idnpInput.removeClass('is-valid is-invalid');
        $patientNameInput.val('').removeClass('is-valid is-invalid');
        setPatientNameMessage('Introduceți cel puțin 2 caractere.', 'text-secondary');

        if (sanitizedIdnp.length === 0) {
            setIdnpMessage('Introduceți IDNP pentru căutare.', 'text-secondary');
            return;
        }

        if (sanitizedIdnp.length < 13) {
            $idnpInput.addClass('is-invalid');
            setIdnpMessage('IDNP trebuie să conțină exact 13 cifre.', 'text-danger');
            return;
        }

        setIdnpMessage('Se caută pacientul...', 'text-secondary');

        $.ajax({
            url: patientSearchUrl,
            method: 'GET',
            dataType: 'json',
            data: { idnp: sanitizedIdnp },
        }).done(function (data) {
            if (currentRequest !== idnpRequestId) {
                return;
            }

            if (!data.valid || !data.found) {
                $idnpInput.addClass('is-invalid');
                setIdnpMessage(data.message || 'Pacientul nu a fost găsit.', 'text-danger');

                if (data.valid) {
                    $addPatientLink.attr('href', `${newPatientUrl}?idnp=${encodeURIComponent(sanitizedIdnp)}`).prop('hidden', false);
                }

                return;
            }

            $idnpInput.addClass('is-valid');
            setIdnpMessage(data.message || 'Pacient găsit.', 'text-success');
            selectPatient(data.patient);
        }).fail(function () {
            if (currentRequest !== idnpRequestId) {
                return;
            }

            $idnpInput.addClass('is-invalid');
            setIdnpMessage('Căutarea pacientului a eșuat.', 'text-danger');
        });
    });

    $patientNameInput.on('input', function () {
        const query = $.trim($patientNameInput.val());
        const currentRequest = ++patientNameRequestId;

        window.clearTimeout(patientNameSearchTimer);
        resetPatient(query.length === 0);
        $idnpInput.val('').removeClass('is-valid is-invalid');
        $patientNameInput.removeClass('is-valid is-invalid');
        setIdnpMessage('Introduceți IDNP pentru căutare.', 'text-secondary');

        if (query.length === 0) {
            setPatientNameMessage('Introduceți cel puțin 2 caractere.', 'text-secondary');
            return;
        }

        if (query.length < 2) {
            $patientNameInput.addClass('is-invalid');
            setPatientNameMessage('Introduceți cel puțin 2 caractere.', 'text-danger');
            return;
        }

        setPatientNameMessage('Se caută pacienți...', 'text-secondary');

        patientNameSearchTimer = window.setTimeout(function () {
            $.ajax({
                url: patientNameSearchUrl,
                method: 'GET',
                dataType: 'json',
                data: { q: query },
            }).done(function (data) {
                if (currentRequest !== patientNameRequestId) {
                    return;
                }

                const patients = Array.isArray(data.patients) ? data.patients : [];

                if (patients.length === 0) {
                    $patientNameInput.addClass('is-invalid');
                    setPatientNameMessage('Nu au fost găsiți pacienți.', 'text-danger');
                    hidePatientNameResults();
                    $addPatientLink.attr('href', newPatientUrl).prop('hidden', false);
                    return;
                }

                $patientNameInput.removeClass('is-invalid');
                setPatientNameMessage(`${patients.length} pacienți găsiți. Alegeți pacientul.`, 'text-success');
                renderPatientNameResults(patients);
            }).fail(function () {
                if (currentRequest !== patientNameRequestId) {
                    return;
                }

                $patientNameInput.addClass('is-invalid');
                setPatientNameMessage('Căutarea pacientului a eșuat.', 'text-danger');
                hidePatientNameResults();
            });
        }, 220);
    });

    $patientNameInput.on('search change', function () {
        if ($.trim($patientNameInput.val()) !== '') {
            return;
        }

        patientNameRequestId += 1;
        window.clearTimeout(patientNameSearchTimer);
        resetPatient(true);
        $idnpInput.val('').removeClass('is-valid is-invalid');
        $patientNameInput.removeClass('is-valid is-invalid');
        setIdnpMessage('Introduceți IDNP pentru căutare.', 'text-secondary');
        setPatientNameMessage('Introduceți cel puțin 2 caractere.', 'text-secondary');
    });

    $organCards.on('click', function () {
        if (!hasPatientAccess()) {
            focusPatientSelection();
            setOrgansDisabled(true);
            clearSelectedOrgans();
            updateFormActions();
            return;
        }

        const $card = $(this);

        if ($card.hasClass('is-gender-disabled') || $card.prop('disabled')) {
            return;
        }

        persistCurrentFormValues();

        const organState = createOrganStateFromCard($card);
        const active = $card.toggleClass('is-active').hasClass('is-active');

        $card.attr('aria-pressed', active ? 'true' : 'false');

        if (active) {
            sessionState.selectedOrgans.set(organState.id, organState);
        } else {
            sessionState.selectedOrgans.delete(organState.id);
        }

        renderSelectedOrgans();
        updateFormActions();

        if (!$examinationForm.prop('hidden')) {
            if (sessionState.selectedOrgans.size === 0) {
                hideExaminationForm();
            } else {
                renderExaminationForm();
            }
        }
    });

    $ultrasoundTypeSelect.on('change', function () {
        applyUltrasoundTypeSelection();
    });

    updateInitialState();
    hydrateInitialSession();
});
