$(function () {
    const sessionState = {
        patient: null,
        selectedOrgans: new Map(),
        isIncognito: false,
    };

    const $main = $('main[data-save-session-url]');
    const $patientBlock = $('#sesion_user_info');
    const $idnpInput = $('#session_idnp');
    const $patientNameInput = $('#session_patient_name');
    const $incognitoPatient = $('[data-patient-incognito]');
    const $imageSection = $('#image_section_image');
    const $organCards = $('.organ-card');
    const $formActions = $('[data-form-actions]');
    const $showExaminationFormButton = $('[data-show-examination-form]');
    const $examinationForm = $('[data-examination-form]');
    const $examinationFormContent = $('[data-examination-form-content]');
    const saveSessionUrl = $main.data('save-session-url');
    const sessionsIndexUrl = $main.data('sessions-index-url');

    function hasPatientAccess() {
        if (sessionState.isIncognito) {
            return true;
        }

        if (sessionState.patient === null) {
            return false;
        }

        return $patientNameInput.length === 0 || $.trim($patientNameInput.val()) !== '';
    }

    function setStartButtonDisabled(isDisabled) {
        $showExaminationFormButton
            .toggleClass('disabled', isDisabled)
            .prop('disabled', isDisabled)
            .attr('aria-disabled', isDisabled ? 'true' : 'false');
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

    function clearSelectedOrgans() {
        sessionState.selectedOrgans.clear();
        $organCards.removeClass('is-active').attr('aria-pressed', 'false');
        $('[data-selected-count]').text('0');
        $('[data-selected-organs]')
            .empty()
            .append($('<span>', {
                class: 'text-secondary',
                text: 'Niciun organ selectat.',
            }));
        $examinationForm.prop('hidden', true);
        $examinationFormContent.empty();
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

    $incognitoPatient.on('change', function () {
        const isChecked = $(this).is(':checked');
        sessionState.isIncognito = isChecked;

        if (isChecked) {
            setPatientFocusState(false);
            setOrgansDisabled(false);
            updateFormActions();
            return;
        }

        setPatientFocusState(true);
        setStartButtonDisabled(true);
        setOrgansDisabled(true);
        clearSelectedOrgans();
        updateFormActions();
    });

    function updateFormActions() {
        const canShowForm = hasPatientAccess() && sessionState.selectedOrgans.size > 0;

        $formActions.prop('hidden', !canShowForm);
        setStartButtonDisabled(!canShowForm);

        if (!canShowForm) {
            $examinationForm.prop('hidden', true);
            $examinationFormContent.empty();
        }
    }

    function createSafeId(value) {
        return value.toString().replace(/[^a-zA-Z0-9_-]/g, '_');
    }

    function createControlField(organ, parameter, side) {
        const sideSuffix = side ? `_${side.key}` : '';
        const fieldId = createSafeId(`organ_${organ.id}${sideSuffix}_parameter_${parameter.id}`);
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
        const sideSuffix = side ? `_${side.key}` : '';
        const noteId = createSafeId(`organ_${organ.id}${sideSuffix}_note`);
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

    function getOrganSides(organ) {
        if (!organ.paired) {
            return [null];
        }

        return [
            { key: 'right', label: 'Dreapta' },
            { key: 'left', label: 'Stânga' },
        ];
    }

    function collectSessionPayload($form) {
        const formElement = $form.get(0);
        const organs = [];

        Array.from(sessionState.selectedOrgans.values()).forEach(function (organ, organIndex) {
            getOrganSides(organ).forEach(function (side) {
                const sideKey = side ? side.key : null;
                const noteId = createSafeId(`organ_${organ.id}${sideKey ? `_${sideKey}` : ''}_note`);
                const parameters = organ.parameters.map(function (parameter, parameterIndex) {
                    const fieldId = createSafeId(`organ_${organ.id}${sideKey ? `_${sideKey}` : ''}_parameter_${parameter.id}`);

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
            patientId: sessionState.isIncognito ? null : Number(sessionState.patient && sessionState.patient.id ? sessionState.patient.id : 0),
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

    function renderExaminationForm() {
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
            text: 'Salvare formular',
        });

        $saveButton.on('click', function () {
            saveExaminationForm($form, $saveMessage, $saveButton);
        });

        $saveActions.append($saveMessage, $saveButton);
        $form.append($saveActions);

        $examinationFormContent.append($form);
        $examinationForm.prop('hidden', false);
        $examinationForm.get(0).scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

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

    $('[data-patient-search]').each(function () {
        const $container = $(this);
        const searchUrl = $container.data('patient-search-url');
        const nameSearchUrl = $container.data('patient-name-search-url');
        const newPatientUrl = $container.data('new-patient-url');
        const $input = $container.find('[data-idnp-input]');
        const $message = $container.find('[data-idnp-message]');
        const $nameInput = $container.find('[data-patient-name-input]');
        const $nameMessage = $container.find('[data-patient-name-message]');
        const $nameResults = $container.find('[data-patient-name-results]');
        const $addPatientLink = $container.find('[data-add-patient-link]');
        const $summary = $container.find('[data-patient-summary]');
        const fields = {
            fullName: $container.find('[data-patient-field="fullName"]'),
            gender: $container.find('[data-patient-field="gender"]'),
            birthYear: $container.find('[data-patient-field="birthYear"]'),
            phone: $container.find('[data-patient-field="phone"]'),
            idnp: $container.find('[data-patient-field="idnp"]'),
            seria: $container.find('[data-patient-field="seria"]'),
            district: $container.find('[data-patient-field="district"]'),
            city: $container.find('[data-patient-field="city"]'),
            address: $container.find('[data-patient-field="address"]'),
            beneficiary: $container.find('[data-patient-field="beneficiary"]'),
        };
        let requestId = 0;
        let nameRequestId = 0;
        let nameSearchTimer = null;

        function setMessage(text, state) {
            $message.text(text).removeClass('text-success text-danger text-secondary').addClass(state);
        }

        function setNameMessage(text, state) {
            if ($nameMessage.length === 0) {
                return;
            }

            $nameMessage.text(text).removeClass('text-success text-danger text-secondary').addClass(state);
        }

        function hideNameResults() {
            $nameResults.empty().prop('hidden', true);
        }

        function resetPatient(shouldFocusPatient = false) {
            sessionState.patient = null;
            $summary.prop('hidden', true);
            $addPatientLink.prop('hidden', true);
            hideNameResults();
            $.each(fields, function (_, $field) {
                $field.text('');
            });
            setOrgansDisabled(!hasPatientAccess());
            if (!hasPatientAccess()) {
                clearSelectedOrgans();
                if (shouldFocusPatient) {
                    focusPatientSelection();
                }
            }
            updateFormActions();
        }

        function renderPatient(patient) {
            fields.fullName.text(`${((patient.lastName || '') + ' ' + (patient.firstName || '')).trim() || '-'}`);
            fields.gender.text(patient.gender || '-');
            fields.birthYear.text(patient.birthYear || '-');
            fields.phone.text(patient.phone || '-');
            fields.idnp.text(patient.idnp || '-');
            fields.seria.text(patient.seria || '-');
            fields.district.text(patient.district || '-');
            fields.city.text(patient.city || '-');
            fields.address.text(patient.address || '-');
            fields.beneficiary.text(patient.beneficiary || 'Nu');
            $summary.prop('hidden', false);
        }

        function getPatientFullName(patient) {
            return `${patient.lastName || ''} ${patient.firstName || ''}`.trim();
        }

        function selectPatient(patient) {
            sessionState.patient = patient;
            $addPatientLink.prop('hidden', true);
            $input.val(patient.idnp || '').removeClass('is-invalid');
            $input.toggleClass('is-valid', Boolean(patient.idnp));

            if ($nameInput.length > 0) {
                $nameInput.val(getPatientFullName(patient)).removeClass('is-invalid').addClass('is-valid');
            }

            setMessage('Pacient selectat.', 'text-success');
            setNameMessage('Pacient selectat.', 'text-success');
            hideNameResults();
            renderPatient(patient);
            updateFormActions();
            setPatientFocusState(false);
            setOrgansDisabled(false);
        }

        function renderNameResults(patients) {
            $nameResults.empty();

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

                $nameResults.append($resultButton);
            });

            $nameResults.prop('hidden', patients.length === 0);
        }

        $input.on('input', function () {
            const sanitizedIdnp = $input.val().replace(/\D/g, '').slice(0, 13);
            const currentRequest = ++requestId;

            $input.val(sanitizedIdnp);
            resetPatient();
            $input.removeClass('is-valid is-invalid');

            if ($nameInput.length > 0) {
                $nameInput.val('').removeClass('is-valid is-invalid');
                setNameMessage('Introduceți cel puțin 2 caractere.', 'text-secondary');
            }

            if (sanitizedIdnp.length === 0) {
                setMessage('Introduceți IDNP pentru căutare.', 'text-secondary');
                return;
            }

            if (sanitizedIdnp.length < 13) {
                $input.addClass('is-invalid');
                setMessage('IDNP trebuie să conțină exact 13 cifre.', 'text-danger');
                return;
            }

            setMessage('Se caută pacientul...', 'text-secondary');

            $.ajax({
                url: searchUrl,
                method: 'GET',
                dataType: 'json',
                data: { idnp: sanitizedIdnp },
            }).done(function (data) {
                if (currentRequest !== requestId) {
                    return;
                }

                if (!data.valid || !data.found) {
                    $input.addClass('is-invalid');
                    setMessage(data.message || 'Pacientul nu a fost găsit.', 'text-danger');

                    if (data.valid) {
                        $addPatientLink.attr('href', `${newPatientUrl}?idnp=${encodeURIComponent(sanitizedIdnp)}`).prop('hidden', false);
                    }

                    return;
                }

                $input.addClass('is-valid');
                setMessage(data.message || 'Pacient găsit.', 'text-success');
                selectPatient(data.patient);
            }).fail(function () {
                if (currentRequest !== requestId) {
                    return;
                }

                $input.addClass('is-invalid');
                setMessage('Căutarea pacientului a eșuat.', 'text-danger');
            });
        });

        if ($nameInput.length > 0 && nameSearchUrl) {
            function handleEmptyNameSearch() {
                if ($.trim($nameInput.val()) !== '') {
                    return;
                }

                nameRequestId += 1;
                window.clearTimeout(nameSearchTimer);
                resetPatient(true);
                $input.val('').removeClass('is-valid is-invalid');
                setMessage('Introduceți IDNP pentru căutare.', 'text-secondary');
                $nameInput.removeClass('is-valid is-invalid');
                setNameMessage('Introduceți cel puțin 2 caractere.', 'text-secondary');
            }

            $nameInput.on('input', function () {
                const query = $.trim($nameInput.val());
                const currentRequest = ++nameRequestId;

                window.clearTimeout(nameSearchTimer);
                resetPatient(query.length === 0);
                $input.val('').removeClass('is-valid is-invalid');
                setMessage('Introduceți IDNP pentru căutare.', 'text-secondary');
                $nameInput.removeClass('is-valid is-invalid');

                if (query.length === 0) {
                    setNameMessage('Introduceți cel puțin 2 caractere.', 'text-secondary');
                    return;
                }

                if (query.length < 2) {
                    $nameInput.addClass('is-invalid');
                    setNameMessage('Introduceți cel puțin 2 caractere.', 'text-danger');
                    return;
                }

                setNameMessage('Se caută pacienți...', 'text-secondary');

                nameSearchTimer = window.setTimeout(function () {
                    $.ajax({
                        url: nameSearchUrl,
                        method: 'GET',
                        dataType: 'json',
                        data: { q: query },
                    }).done(function (data) {
                        if (currentRequest !== nameRequestId) {
                            return;
                        }

                        const patients = Array.isArray(data.patients) ? data.patients : [];

                        if (patients.length === 0) {
                            $nameInput.addClass('is-invalid');
                            setNameMessage('Nu au fost găsiți pacienți.', 'text-danger');
                            hideNameResults();
                            $addPatientLink.attr('href', newPatientUrl).prop('hidden', false);
                            return;
                        }

                        $nameInput.removeClass('is-invalid');
                        setNameMessage(`${patients.length} pacienți găsiți. Alegeți pacientul.`, 'text-success');
                        renderNameResults(patients);
                    }).fail(function () {
                        if (currentRequest !== nameRequestId) {
                            return;
                        }

                        $nameInput.addClass('is-invalid');
                        setNameMessage('Căutarea pacientului a eșuat.', 'text-danger');
                        hideNameResults();
                    });
                }, 220);
            });

            $nameInput.on('search change', handleEmptyNameSearch);
        }
    });

    $('[data-organ-grid]').each(function () {
        const $grid = $(this);
        const $cards = $grid.find('[data-organ-card]');
        const $selectedContainer = $('[data-selected-organs]');
        const $selectedCount = $('[data-selected-count]');

        function renderSelected() {
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

        $cards.on('click', function () {
            if (!hasPatientAccess()) {
                focusPatientSelection();
                setOrgansDisabled(true);
                clearSelectedOrgans();
                updateFormActions();
                return;
            }

            const $card = $(this);
            const id = $card.data('organ-id').toString();
            let parameters = [];

            try {
                parameters = JSON.parse($card.attr('data-organ-parameters') || '[]');
            } catch (error) {
                parameters = [];
            }

            const active = $card.toggleClass('is-active').hasClass('is-active');
            $card.attr('aria-pressed', active ? 'true' : 'false');

            if (active) {
                sessionState.selectedOrgans.set(id, {
                    id,
                    name: $card.data('organ-name'),
                    paired: $card.data('organ-paired').toString() === '1',
                    imagePath: $card.data('organ-image-path'),
                    parameters,
                });
            } else {
                sessionState.selectedOrgans.delete(id);
            }

            renderSelected();
            updateFormActions();

            if (!$examinationForm.prop('hidden')) {
                renderExaminationForm();
            }
        });

        renderSelected();
        updateFormActions();
    });

    updateInitialState();
});
