/**
 * @link https://docs.moodle.org/dev/Javascript/Coding_Style
 * @link https://docs.moodle.org/dev/AJAX
 * @link https://docs.moodle.org/dev/Exceptions
 */

/**
 * Code Javascript nécessaire pour le block chatbot_rasa.
 *
 * @package   chatbot_rasa
 * @copyright 2021 José-Paul Blülle Celado
 */

// TODO : cliquer vers le haut des boutons doit être possible (CSS).
// TODO : scrollIntoView // scroll-behavior: smooth; CSS // scrollTo // JQuery ?
// TODO : vérifier le code (nom de variables, espacements, si quelque chose est fait 1 fois alors on le refait de la même manière, ...).
// TODO : vérifier le code HTML (nom de variables !! + structure optimale pour le CSS).

// Nécessaire pour ne pas avoir à supprimer et recréer les eventListener.
var lstQuestions = null,
	filteredLstQuestions = null,
	// Pour pouvoir créer / supprimer les eventListener.
	time = Date.now(),
	nbMessage = 0;

/**
 * Envoi l'input de l'utilisateur à Rasa pour
 * recevoir une réponse et l'afficher.
 *
 * @method sendMessageToRasa
 * @param {String} text L'input de l'utilisateur.
 */
const sendMessageToRasa = (text) => {
	require(['core/ajax'], (ajax) => {
		var userInput = document.getElementById('m-user-input'),
			promises = ajax.call([
				{ methodname: 'moodle_chatbot_rasa_post_message', args: { courseid: courseId, text: text } }
			]);

		promises[0].done((response) => {
			var responseParsed = JSON.parse(response);

			if (responseParsed == null) {
				newMessageDisplay(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), false, -1, true);

			} else if (responseParsed.error) { // Uniquement si la vérification JS est désactivée, sinon on ne devrait jamais arriver ici !
				newMessageDisplay(responseParsed.error, false, -1, true);

			} else if (responseParsed['buttons'] != null) {
				newMessageListChoiceDisplay(responseParsed['text'], responseParsed['buttons'], responseParsed['id']);

			} else {
				newMessageDisplay(responseParsed['text'], false, responseParsed['id']);
			}
			userInput.addEventListener('keypress', handleSendMessage, false);

		}).fail(ex => {
			newMessageDisplay(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), false, -1, true);
			userInput.addEventListener('keypress', handleSendMessage, false);
		});
	});
}

/**
 * Ecrit la question en paramètre dans la base de données pour Rasa.
 *
 * @method insertQuestion
 * @param {String} subject 	Le sujet en rapport avec la question.
 * @param {String} question La question.
 * @param {String} response La réponse à la question.
 */
const insertQuestion = (subject, question, response) => {
	require(['core/ajax'], (ajax) => {
		var promises = ajax.call([
			{ methodname: 'moodle_chatbot_rasa_create_question', args: { courseid: courseId, subject: subject, question: question, response: response } }
		]);

		promises[0].done((resp) => {
			var responseParsed = JSON.parse(resp);

			if (responseParsed.error) {
				showResult(responseParsed.error, 'm-add-result', true);

			} else {
				var inst = { 'id': responseParsed, 'subject': subject, 'question': question, 'response': response };

				if (lstQuestions == null) {
					lstQuestions = [inst];

				} else {
					lstQuestions.splice(sortedIndex(lstQuestions, inst, ['subject', 'question']), 0, inst);
				}
				reloadQuestions();
				showResult(M.util.get_string('validation_question_saved', 'block_chatbot_rasa'), 'm-add-result');
			}

		}).fail(ex => {
			showResult(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), 'm-add-result', true);
		});
	});
}

/**
 * Modifie la question avec l'id
 * et les champs en paramètre.
 *
 * @method modifyQuestion
 * @param {Number} id L'id de la question.
 * @param {String} subject Le sujet.
 * @param {String} question La question.
 * @param {String} response La réponse à la question.
 */
const modifyQuestion = (id, subject, question, response) => {
	if (!isNaN(id) && parseInt(id) > 0 && document.getElementById('select' + id) != null) {
		require(['core/ajax'], (ajax) => {
			var promises = ajax.call([
				{ methodname: 'moodle_chatbot_rasa_modify_question', args: { courseid: courseId, id: id, subject: subject, question: question, response: response } }
			]);

			promises[0].done((resp) => {
				var responseParsed = JSON.parse(resp);

				if (responseParsed.error) {
					showResult(responseParsed.error, 'm-modify-delete-result', true);

				} else {
					var inst = { 'id': id, 'subject': subject, 'question': question, 'response': response },
						index = filteredLstQuestions == null ? null : filteredLstQuestions.findIndex(x => x.id == id),
						suitedForFilter = inst[document.getElementById('m-search-field').value].toLowerCase().includes(document.getElementById('m-search').value.toLowerCase());

					// Vérifie si la question modifiée convient au filtre et l'ajoute dans la liste filtrée si elle n'est pas encore présente.
					if (filteredLstQuestions != null && index == null && suitedForFilter) {
						filteredLstQuestions.splice(sortedIndex(filteredLstQuestions, inst, ['subject', 'question']), 0, inst);

					} else if (index != null) { // La supprime si elle est présente et la rajoute si elle convient au filtre.
						filteredLstQuestions.splice(index, 1);
						if (suitedForFilter) {
							filteredLstQuestions.splice(sortedIndex(filteredLstQuestions, inst, ['subject', 'question']), 0, inst);
						}
					}
					// Supprime l'ancienne instance.
					lstQuestions.splice(lstQuestions.findIndex(x => x.id == id), 1);
					// Insère l'élément dans le bon index pour que la liste reste triée. -> plus rapide que de tout retrier.
					lstQuestions.splice(sortedIndex(lstQuestions, inst, ['subject', 'question']), 0, inst);
					reloadQuestions();
					var select = document.getElementById('select' + id);
					if (select != null) { // On ne peut pas utliser la variable "select" avant car on a "reloadQuestions()".
						select.checked = true;

					} else {
						document.getElementById('m-field-id').textContent = '';
						document.getElementById('m-field-subject').value = '';
						document.getElementById('m-field-question').value = '';
						document.getElementById('m-field-response').value = '';
					}
					showResult(M.util.get_string('validation_question_modified', 'block_chatbot_rasa'), 'm-modify-delete-result');
				}

			}).fail(ex => {
				showResult(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), 'm-modify-delete-result', true);
			});
		});

	} else if (id == '') {
		showResult(M.util.get_string('error_change_or_delete_no_selection', 'block_chatbot_rasa'), 'm-modify-delete-result', true);

	} else {
		// l'id < 0 ou pas un chiffre et pas ''
		showResult(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), 'm-modify-delete-result', true);
	}
}

/**
 * Supprime de la base de donnée
 * la question avec l'id en paramètre.
 *
 * @method deleteQuestion
 * @param {String} id L'id de la question à supprimer.
 */
const deleteQuestion = (id) => {
	// Vérifie si l'utilisateur a modifié l'id.
	var select = document.getElementById('select' + id);

	if (!isNaN(id) && (parseInt(id) > 0 || parseInt(id) == -2) && (select != null || parseInt(id) == -2)) {
		require(['core/ajax'], (ajax) => {
			var promises = ajax.call([
				{ methodname: 'moodle_chatbot_rasa_delete_question', args: { courseid: courseId, id: id } }
			]);

			promises[0].done((resp) => {
				var responseParsed = JSON.parse(resp);

				if (responseParsed.error) {
					showResult(responseParsed.error, 'm-modify-delete-result', true);

				} else {
					if (id == -2) { // Pas utilisé pour l'instant.
						lstQuestions = [];
						filteredLstQuestions = null;
						reloadQuestions();
						showResult(M.util.get_string('validation_question_deleted_all', 'block_chatbot_rasa'), 'm-modify-delete-result');

					} else {
						var ul = select.parentNode;
						// Suppression dans la liste et l'HTML, pas besoin de recharger.
						lstQuestions.splice(lstQuestions.findIndex(x => x.id == id), 1);
						ul.parentNode.childElementCount > 2 ? ul.remove() : ul.parentNode.remove();
						showResult(M.util.get_string('validation_question_deleted', 'block_chatbot_rasa'), 'm-modify-delete-result');
					}
					document.getElementById('m-field-id').textContent = '';
					document.getElementById('m-field-subject').value = '';
					document.getElementById('m-field-question').value = '';
					document.getElementById('m-field-response').value = '';
				}

			}).fail(ex => {
				showResult(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), 'm-modify-delete-result', true); // Enregister l'exception ? Car c'est une vraie erreur. -> log pour les développeurs à faire !
			});
		});

	} else {
		showResult(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), 'm-modify-delete-result', true);
	}
}

/**
 * Déplace les questions de l'ancien cours id
 * dans celui du context actuel.
 *
 * @method transferFromOldCourse
 * @param {String} oldCourseId Le cours id aux questions transférer.
 */
const transferFromOldCourse = (oldCourseId) => {
	if (isNaN(oldCourseId)) {
		showResult(M.util.get_string('error_transfer_question_courseid_not_number', 'block_chatbot_rasa', { courseid: 255 }), 'm-transfer-result');

	} else if (parseInt(oldCourseId) < 2) {
		showResult(M.util.get_string('error_transfer_question_courseid_min', 'block_chatbot_rasa'), 'm-transfer-result');

	} else if (parseInt(oldCourseId) == courseId) {
		showResult(M.util.get_string('error_transfer_question_courseid_same', 'block_chatbot_rasa'), 'm-transfer-result');

	} else {
		require(['core/ajax'], (ajax) => {
			var promises = ajax.call([
				{ methodname: 'moodle_chatbot_rasa_transfer_question', args: { courseid: courseId, oldcourseid: oldCourseId } }
			]);

			promises[0].done((response) => {
				var responseParsed = JSON.parse(response);

				if (responseParsed.error) {
					showResult(responseParsed.error, 'm-transfer-result', true);

				} else {
					showResult(M.util.get_string('validation_transfer_question_executed', 'block_chatbot_rasa'), 'm-transfer-result');
				}

			}).fail(ex => {
				showResult(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), 'm-transfer-result', true); // Enregister l'exception ? Car c'est une vraie erreur. -> log pour les développeurs à faire !
			});
		});
	}
}

/**
 * Récupère et écrit un message de bienvenue.
 *
 * @method welcomeMessage
 */
const welcomeMessage = _=> {
	require(['core/ajax'], (ajax) => {
		var promises = ajax.call([
			{ methodname: 'moodle_chatbot_rasa_trigger_welcome', args: { courseid: courseId } }
		]);

		promises[0].done((response) => {
			var responseParsed = JSON.parse(response);

			if (responseParsed.error) {
				newMessageDisplay(responseParsed.error, false, -1, true, false);

			} if (responseParsed['buttons'] != null) {
				var responses = responseParsed['text'].split('\n'),
					finalResponse = responses.pop();

				for (const resp of responses) {
					newMessageDisplay(resp, false, -1, false, false);
				}
				newMessageListChoiceDisplay(finalResponse, responseParsed['buttons'], -1, false);

			} else {
				var responses = responseParsed['text'].split('\n');
				for (const resp of responses) {
					newMessageDisplay(resp, false, -1, false, false);
				}
			}

		}).fail(ex => {
			newMessageDisplay(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), false, -1, true, false); // Enregister l'exception ? Car c'est une vraie erreur. -> log pour les développeurs à faire !
		});
	});
}

/**
 * Récupère toutes les questions d'un cours
 * et instancie la
 * recherche / modification / suppression.
 *
 * @method getQuestions
 */
const getQuestions = (functCallBack = null) => {
	require(['core/ajax'], (ajax) => {
		var promises = ajax.call([
			{ methodname: 'moodle_chatbot_rasa_get_question', args: { courseid: courseId } }
		]);

		promises[0].done((response) => {
			var responseParsed = JSON.parse(response); // [{'id': 1, 'subject': 'subject 1', 'question': 'question 1', 'response': 'response 1'}, {...}, ...]

			if (responseParsed.error) {
				showResult(responseParsed.error, 'm-modify-delete-result', true);

			} else {
				var isNull = lstQuestions == null;

				lstQuestions = responseParsed;
				// Tri du côté du client.
				lstQuestions.sort((a, b) => {
					if (a.subject != b.subject) {
						return a.subject > b.subject ? 1 : -1;
					}
					return a.question > b.question ? 1 : -1;
				});
				if (document.getElementById('m-show-questions') != null) {
					isNull ? initQuestions() : reloadQuestions();
				}
				if (functCallBack != null) {
					functCallBack();
				}
			}

		}).fail(ex => {
			showResult(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), 'm-modify-delete-result', true); // Enregister l'exception ? Car c'est une vraie erreur. -> log pour les développeurs à faire !
		});
	});
}

/**
 * Marque le ressenti d'un utilisateur (positif ou non) à
 * une interaction (question-réponse de Rasa).
 *
 * @method interactSetHasHelped
 * @param {Number} id         L'id de l'interaction.
 * @param {Bool}   isPositive Si l'interaction est positive.
 */
const interactSetHasHelped = (id, hasHelped) => {
	if (id < 1) {
		newMessageDisplay(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), false, -1, true);

	} else {
		require(['core/ajax'], (ajax) => {
			var promises = ajax.call([
				{ methodname: 'moodle_chatbot_rasa_set_has_helped', args: { courseid: courseId, id: id, hashelped: hasHelped } }
			]);

			promises[0].done((resp) => {
				var responseParsed = JSON.parse(resp);

				if (responseParsed.error) {
					newMessageDisplay(responseParsed.error, false, -1, true);

				} else {
					// Faire quelque chose pour confirmer ? Par exemple, changer la couleur du message ?
				}

			}).fail(ex => {
				newMessageDisplay(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), false, -1, true); // Enregister l'exception ? Car c'est une vraie erreur. -> log pour les développeurs à faire !
			});
		});
	}
}

/**
 * Test si l'utilisateur SPAM.
 *
 * @method testSpam
 * @param {Number} time Le temps à vérifier.
 * @param {Number} nbMessage L'input de l'utilisateur.
 * @return {[Number, Number]} Le temps depuis le compte à rebour
 * et le nombre de messages envoyés durant ce temps.
 * @throws SPAM de l'utilisateur.
 */
const testSpam = (time, nbMessage) => {
	var delay = 60 * 1000,
		maxMessageInDelay = 15,
		timeNow = Date.now();

	if (timeNow < time + delay) {
		if (nbMessage >= maxMessageInDelay) {
			throw M.util.get_string('error_spam', 'block_chatbot_rasa', { seconds: (Math.ceil((time + delay - timeNow) / 1000)) });

		} else {
			return [time, nbMessage + 1];
		}

	} else {
		return [Date.now(), 1];
	}
}

/**
 * Test l'input de l'utilisateur.
 *
 * @method testInput
 * @param {String} textTrimmed L'input de l'utilisateur trim().
 * @throws Erreur de l'input donné.
 */
const testInput = (textTrimmed) => {
	var textWithoutSpace = textTrimmed.replace(/\s/g,'');

	if (!textWithoutSpace) {
  		throw M.util.get_string('error_empty_string', 'block_chatbot_rasa');

	} else if (textTrimmed.length > 255) {
		throw M.util.get_string('error_max_length_string', 'block_chatbot_rasa', { maxlength: 255 });

	} /*else if (textWithoutSpace.length < 5) {
		throw 'Nombre de caractères insuffisants (5 minimum).';
	} */
}

/**
 * Gère le clique pour marquer le ressenti de l'utilisateur
 * à une interaction (question-réponse de Rasa). "function"
 * pour l'accès à "this" == "evt.target".
 *
 * @method handleStat
 */
function handleStat() {
	var hasHelped = this.value == '+',
		id = this.parentNode.parentNode.id;

	// Suppression des events pour le ".remove()" après.
	this.removeEventListener('click', handleStat, false);
	(hasHelped ? this.nextElementSibling || this.nextSibling : this.previousElementSibling || this.previousSibling).removeEventListener('click', handleStat, false);

	if (id < 1) {
		newMessageDisplay(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), false, -1, true);

	} else {
		interactSetHasHelped(id, hasHelped);
	}
	this.parentNode.remove();
}

/**
 * Affiche un messgae dans l'historique.
 *
 * @method newMessageDisplay
 * @param {String} text Le texte à afficher.
 * @param {Bool} sentFromUser Si c'est écrit par l'utilisateur.
 * @param {Number} id L'id de l'interaction.
 * @param {Bool} error Si c'est une erreur.
 * @param {Bool} scroll Si on scroll sur le message.
 */
const newMessageDisplay = (text, sentFromUser = true, id = -1, error = false, scroll = true) => { // Peut être object "options" à la place de tous ces paramètres ?
	var newMessage = document.createElement('li'),
		listMessages = document.getElementById('m-list-messages');

	newMessage.innerText = text;
	if (id > 0 && scroll && !error && !sentFromUser) {
		var div = document.createElement('div'),
			statUp = document.createElement('button'),
			statDown = document.createElement('button'),
			statDisplay = document.createElement('button');

		div.setAttribute('class', 'm-element-chat__message-stat-div');
		statUp.setAttribute('class', 'm-element-chat__message-stat m-element-chat__message-stat--up');
		statDown.setAttribute('class', 'm-element-chat__message-stat m-element-chat__message-stat--down');
		statDisplay.setAttribute('class', 'm-element-chat__message-stat--display');
		newMessage.id = id;
		statUp.value = '+';
		statDown.value = '-';
		div.appendChild(statUp);
		div.appendChild(statDown);
		div.appendChild(statDisplay);
		newMessage.prepend(div);
		statUp.addEventListener('click', handleStat, false);
		statDown.addEventListener('click', handleStat, false);
	}
	newMessage.setAttribute('class',
		(sentFromUser ? 'm-element-chat-message m-element-chat-message--right' : 'm-element-chat-message m-element-chat-message--left') +
		(error ? ' m-element-chat-message--error' : '')
	);
	listMessages.append(newMessage);
	if (scroll) {
		newMessage.scrollIntoView( { behavior: 'smooth', block: 'start', inline: 'start' } );
	}
}

/**
 * Affiche un messgae dans l'historique.
 *
 * @method newMessageListChoiceDisplay
 * @param {String} text Le texte de début.
 * @param {Array} list La liste d'objets avec les titres
 * des choix à afficher et leur valeur.
 * @param {Number} id L'id de l'interaction.
 * @param {Bool} scroll Si on scroll sur le message.
 */
const newMessageListChoiceDisplay = (text, list, id = -1, scroll = true) => { // Peut être object "options" à la place de tous ces paramètres ?
	var listMessages = document.getElementById('m-list-messages'),
		newMessage = document.createElement('li'),
		startText = document.createElement('span'),
		fragment = new DocumentFragment(),
		button = document.createElement('button'),
		userInput = document.getElementById('m-user-input');

	// Création du texte et des boutons.
	startText.innerText = text + '\n';
	newMessage.appendChild(startText);
	button.setAttribute('class', 'm-element-chat__messages-list-choice');
	for (i = 0; i < list.length; i++) {
		button = button.cloneNode(false);
		button.innerText = list[i]['title'];
		button.value = list[i]['payload'];
		fragment.appendChild(button);
	}
	newMessage.appendChild(fragment);
	newMessage.setAttribute('class', 'm-element-chat-message m-element-chat-message--list m-element-chat-message--left');
	// S'il y a les boutons de stats.
	if (id > 0 && scroll) {
		var div = document.createElement('div'),
			statUp = document.createElement('button'),
			statDown = document.createElement('button'),
			statDisplay = document.createElement('button');

		div.setAttribute('class', 'm-element-chat__message-stat-div');
		statUp.setAttribute('class', 'm-element-chat__message-stat m-element-chat__message-stat--up');
		statDown.setAttribute('class', 'm-element-chat__message-stat m-element-chat__message-stat--down');
		statDisplay.setAttribute('class', 'm-element-chat__message-stat--display');
		newMessage.id = id;
		statUp.value = '+';
		statDown.value = '-';
		div.appendChild(statUp);
		div.appendChild(statDown);
		div.appendChild(statDisplay);
		newMessage.prepend(div);
		statUp.addEventListener('click', handleStat, false);
		statDown.addEventListener('click', handleStat, false);
	}
	// Event sur le clique des boutons.
	newMessage.addEventListener('click', function handleClickChoice(evt) {
		var targetButton = evt.target;

		/**
		 * Donne la réponse à une question d'un sujet.
		 * Fait office de callback pour que
		 * "lstQuestions" ne soit pas null.
		 *
		 * @method findResponse
		 */
		const findResponse = _=> {
			var inst = lstQuestions.find(x => x.subject == targetButton.value && x.question == targetButton.innerText);

			if (inst == null) { // La question a été supprimée.
				newMessageDisplay(M.util.get_string('error_not_listed', 'block_chatbot_rasa'), false, -1, true);

			} else {
				newMessageDisplay(inst.response, false);
			}
		}

		if (targetButton.nodeName == 'BUTTON' && targetButton.parentNode.nodeName != 'DIV') {
			userInput.removeEventListener('keypress', handleSendMessage, false);
			newMessage.removeEventListener('click', handleClickChoice, false);
			while (newMessage.lastChild.nodeName != 'SPAN') {
				newMessage.removeChild(newMessage.lastChild);
			}
			newMessageDisplay(targetButton.innerText);
			// Différencier les boutons pour réponse - boutons pour rasa
			if (targetButton.value.startsWith('/')) {
				sendMessageToRasa(targetButton.value);

			} else if (lstQuestions == null || lstQuestions == []) { // Recharge les questions de la BDD avec une actualisation de la page.
				getQuestions(findResponse);

			} else {
				findResponse();
			}
			userInput.addEventListener('keypress', handleSendMessage, false);
		}
	}, false);

	// Ajoute le message dans la liste.
	listMessages.appendChild(newMessage);
	if (scroll) {
		newMessage.scrollIntoView( { behavior: 'smooth', block: 'start', inline: 'start' } );
	}
}

/**
 * Filtre et recharge la liste des questions
 * dans l'HTML.
 *
 * @method reloadQuestions
 * @param {Array} list La liste des questions.
 */
const reloadQuestions = _=> {
	var listQuestions = document.getElementById('m-list-questions'),
		span = document.createElement('span'),
		li = document.createElement('li'),
		ul = document.createElement('ul'),
		input = document.createElement('input'),
		label = document.createElement('label'),
		fragment = new DocumentFragment(),
		lastSubject = null,
		list,
		id = document.getElementById('m-field-id'),
		inputSearch = document.getElementById('m-search'),
		filteredField = document.getElementById('m-search-field');

	// Si on ne cherche rien.
	if (inputSearch.value == '') {
		filteredLstQuestions = null;
		list = lstQuestions;

	} else { // Sinon filtre.
		filteredLstQuestions = lstQuestions.filter(x => x[filteredField.value].toLowerCase().includes(inputSearch.value.toLowerCase()));
		list = filteredLstQuestions;
	}

	// Initialisation des éléments par défaut.
	document.getElementById('m-modify-delete-result').hidden = true;
	span.setAttribute('class', 'm-element-chat__questions-subject');
	ul.setAttribute('class', 'm-element-chat__questions-ul');
	label.setAttribute('class', 'm-element-chat__questions-question');
	input.setAttribute('class', 'm-element-chat__questions-radio');
	input.type = 'radio';
	input.name = 'select';
	input.hidden = true;
	listQuestions.innerHTML = ''; // Ne doit pas avoir d'enfants avec des events listener !

	// La liste doit être triée par sujet pour avoir
	// les questions regroupées pour un même sujet.
	for (const inst of list) {
		var ulClone = ul.cloneNode(false),
			inputClone = input.cloneNode(false),
			labelClone = label.cloneNode(false),
			liClone,
			spanClone;

		if (lastSubject == null || lastSubject != inst.subject) {
			lastSubject = inst.subject;
			liClone = li.cloneNode(false);
			spanClone = span.cloneNode(false);
			spanClone.innerText = lastSubject;
			spanClone.id = lastSubject;
			liClone.appendChild(spanClone);
		}
		liClone.appendChild(ulClone);
		ulClone.appendChild(inputClone);
		ulClone.appendChild(labelClone);
		labelClone.setAttribute('for', 'select' + inst.id);
		inputClone.id = 'select' + inst.id;
		inputClone.value = inst.id;
		labelClone.innerText = inst.question;
		fragment.appendChild(liClone);
	}
	listQuestions.appendChild(fragment);
	// Garde l'instance sélectionnée si elle est présente, sinon la désélectionne.
	if (document.getElementById('select' + id.textContent) != null) {
		document.getElementById('select' + id.textContent).checked = true;

	} else {
		var subject = document.getElementById('m-field-subject'),
			question = document.getElementById('m-field-question'),
			response = document.getElementById('m-field-response');

		id.textContent = subject.value = question.value = response.value = '';
	}
}

/**
 * Récupère toutes les questions d'un cours
 * et instancie la
 * recherche / modification / suppression.
 *
 * @method initQuestions
 * @param {Array} list La liste de questions.
 */
const initQuestions = _=> {
	var listQuestions = document.getElementById('m-list-questions'),
		id = document.getElementById('m-field-id'),
		subject = document.getElementById('m-field-subject'),
		question = document.getElementById('m-field-question'),
		response = document.getElementById('m-field-response'),
		inputSearch = document.getElementById('m-search'),
		buttonModify = document.getElementById('m-modify-question'),
		buttonDelete = document.getElementById('m-delete-question'),
		buttonDeleteAll = document.getElementById('m-delete-question-all'),
		filteredField = document.getElementById('m-search-field');

	id.textContent = subject.value = question.value = response.value = '';

	listQuestions.addEventListener('change', (evt) => {
		if (evt.target.type == 'radio') {
			id.textContent = evt.target.value;
			var inst = lstQuestions.find(x => x.id == id.textContent);
			subject.value = inst.subject;
			question.value = inst.question;
			response.value = inst.response;
		}
	}, false);

	buttonModify.addEventListener('click', _=> {
		try {
			testInput(subject.value);
			testInput(question.value);
			testInput(response.value);
			modifyQuestion(id.textContent, subject.value, question.value, response.value);

		} catch (ex) {
			showResult(ex, 'm-modify-delete-result', true); // On ne dit pas qui a l'erreur !
		}
	}, false);

	buttonDelete.addEventListener('click', _=> {
		deleteQuestion(id.textContent);
	}, false);

	buttonDeleteAll.addEventListener('click', _=> {
		if (confirm('Voulez-vous vraiment supprimer toutes les questions de ce cours ?')) {
			deleteQuestion(-2);
		}
	}, false);

	inputSearch.addEventListener('input', reloadQuestions, false);
	filteredField.addEventListener('change', reloadQuestions, false);
	reloadQuestions();
}

/**
 * Affiche dans un élément un texte et
 * lui donne un style défini par le booléen
 * "error".
 *
 * @method showResult
 * @param {String} text Le texte à afficher.
 * @param {String} nodeId L'id de l'élément HTML.
 * @param {Bool} error Si c'est une erreur.
 */
const showResult = (text, nodeId, error = false) => {
	var field = document.getElementById(nodeId);

	// Pas très bon ? Mieux avoir 1 seule class ?
	if (nodeId == 'm-modify-delete-result') {
		field.setAttribute('class', error ? 'm-element-chat__questions-error' : 'm-element-chat__questions-valid');

	} else {
		field.setAttribute('class', error ? 'm-element-chat__options-error' : 'm-element-chat__options-valid');
	}
	field.innerText = text;
	field.hidden = false;
}

/**
 * Gère l'envoi d'un message
 * pour l'envoyer à Rasa (confirmation).
 *
 * @method handleSendMessage
 * @param {Object} evt L'event.
 */
const handleSendMessage = (evt) => {
	var userInput = document.getElementById('m-user-input'),
		userInputTrimmed = userInput.value.trim();

	// Si c'est la touche 'Enter' et que l'input n'est pas vide.
	if (evt.keyCode == 13 && userInput.value.length > 0) {
		userInput.removeEventListener('keypress', handleSendMessage, false);
		try {
			// Création du message et ajout sur l'historique.
			newMessageDisplay(userInputTrimmed);
			// Différents tests.
			[time, nbMessage] = testSpam(time, nbMessage);
			testInput(userInputTrimmed);
			// Envoie la requête à Rasa pour retourner la réponse.
			sendMessageToRasa(userInputTrimmed);

		} catch (ex) {
			// L'erreur n'est pas sauvegardée niveau serveur. -> log à faire !
			userInput.addEventListener('keypress', handleSendMessage, false);
			newMessageDisplay(ex, false, -1, true);
		}
		// Vide l'input.
		userInput.value = '';
	}
}

/**
 * Instanciation au début de chargement
 * de la page.
 *
 * @method init
 */
const init = _=> {
	var buttonReduce = document.getElementById('m-reduce-chat'),
		buttonChat = document.getElementById('m-show-chat'),
		buttonOptions = document.getElementById('m-show-options'),
		buttonQuestions = document.getElementById('m-show-questions'),
		interfaceChat = document.getElementById('m-chat-home'),
		interfaceOptions = document.getElementById('m-chat-options'),
		interfaceQuestions = document.getElementById('m-chat-questions');

	// Envoi de l'input utilisateur.
	document.getElementById('m-user-input').addEventListener('keypress', handleSendMessage, false);

	// Uniqument disponible par les utilisateurs pouvant modifier le cours.
	if (buttonOptions && buttonQuestions) {
		var buttonAddConfirm = document.getElementById('m-add-confirm'),
			buttonTransferConfirm = document.getElementById('m-transfer-confirm'),
			addSubject = document.getElementById('m-add-subject'),
			addQuestion = document.getElementById('m-add-question'),
			addResponse = document.getElementById('m-add-response'),
			transferCourseId = document.getElementById('m-transfer-course-id'),
			spanAddResult = document.getElementById('m-add-result'),
			spanTransferResult = document.getElementById('m-transfer-result'),
			spanModifyDeleteResult = document.getElementById('m-transfer-result');

		// Montre le menu chat
		buttonChat.addEventListener('click', _=> {
			buttonReduce.value = '-';
			interfaceOptions.hidden = interfaceQuestions.hidden = spanAddResult.hidden = spanTransferResult.hidden = spanModifyDeleteResult.hidden = true;
			interfaceChat.hidden = false;
		}, false);

		// Montre le menu options
		buttonOptions.addEventListener('click', _=> {
			buttonReduce.value = '-';
			interfaceChat.hidden = interfaceQuestions.hidden = spanModifyDeleteResult.hidden = true;
			interfaceOptions.hidden = false;
		}, false);

		// Montre le menu questions
		buttonQuestions.addEventListener('click', _=> {
			buttonReduce.value = '-';
			interfaceChat.hidden = interfaceOptions.hidden = spanAddResult.hidden = spanTransferResult.hidden = true;
			interfaceQuestions.hidden = false;
			getQuestions(); // Recharge à chaque clique du menu pour obtenir les modifications.
		}, false);

		buttonAddConfirm.addEventListener('click', _=> {
			addSubject.value = addSubject.value.trim();
			addQuestion.value = addQuestion.value.trim();
			addResponse.value = addResponse.value.trim();

			try {
				testInput(addSubject.value);
				testInput(addQuestion.value);
				testInput(addResponse.value);
				// TODO '\n' doit créer un retour à la ligne !
				if (confirm(M.util.get_string('confirmation_create_question', 'block_chatbot_rasa', { subject: addSubject.value, question: addQuestion.value, response: addResponse.value }))) {
					insertQuestion(addSubject.value, addQuestion.value, addResponse.value);
				}

			} catch (ex) {
				showResult(ex, 'm-add-result', true); // On ne dit pas qui a l'erreur ! -> log à faire !
			}
		}, false);

		buttonTransferConfirm.addEventListener('click', _=> {
			transferCourseIdInt = parseInt(transferCourseId.value);

			if (isNaN(transferCourseIdInt)) {
				showResult(M.util.get_string('error_transfer_question_courseid_not_number', 'block_chatbot_rasa', { courseid: transferCourseId.value }), 'm-transfer-result', true);

			} else if (transferCourseIdInt < 2) {
				showResult(M.util.get_string('error_transfer_question_courseid_min', 'block_chatbot_rasa'), 'm-transfer-result', true);

			} else {
				if (confirm(M.util.get_string('confirmation_transfer_question', 'block_chatbot_rasa', { oldcourseid: transferCourseId.value }))) {
					transferFromOldCourse(transferCourseIdInt);
				}
			}
		}, false);

		// Affiche ou cache les éléments du block.
		buttonReduce.addEventListener('click', _=> {
			if (buttonReduce.value == '-') {
				buttonReduce.value = '+';
				interfaceChat.hidden = interfaceOptions.hidden = interfaceQuestions.hidden = true;

			} else {
				buttonReduce.value = '-';
				interfaceOptions.hidden = interfaceQuestions.hidden = true;
				interfaceChat.hidden = false;
			}
		}, false);

	} else {
		// Affiche ou cache les éléments du block.
		buttonReduce.addEventListener('click', _=> {
			if (buttonReduce.value == '-') {
				buttonReduce.value = '+';
				interfaceChat.hidden = true;

			} else {
				buttonReduce.value = '-';
				interfaceChat.hidden = false;
			}
		}, false);
	}
	// Récupère et écrit un message de bienvenue.
	welcomeMessage();
}

/**
 * Trouve l'index d'un objet pour qu'il soit trié
 * par rapport aux index passés en paramètres.
 *
 * @see https://stackoverflow.com/questions/1344500/efficient-way-to-insert-a-number-into-a-sorted-array-of-numbers
 * @method sortedIndex
 * @param {Array} array La liste triée à comparer.
 * @param {Object} value L'objet à comparer.
 * @param {Array} keys Les clés de l'objet.
 */
const sortedIndex = (array, value, keys) => {
    var low = 0,
        high = array.length,
		i = 0;

    while (low < high) {
		// Division par 2 et arrondi à l'entier inférieur.
		// unsigned right shift, 111 (6) -> 011 (3)
        var mid = (low + high) >>> 1;

        if (array[mid][keys[i]] < value[keys[i]]) {
			low = mid + 1;

		} else if (array[mid][keys[i]] == value[keys[i]]) {
			if (++i > keys.length - 1) {
				return low; // S'il trouve un objet identique, il renvoie son index.
			}

		} else {
			high = mid;
		}
    }
    return low;
}

window.addEventListener('load', function load() {
	window.removeEventListener('load', load, false);
	init();
}, false);
