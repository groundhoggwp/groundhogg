import React, { useEffect, useState } from 'react';
import { registerStepType, SimpleEditModal } from '../steps';
import { Button, Col, Row, Alert, Spinner } from 'react-bootstrap';
import axios from 'axios';
import { Dashicon } from '../../components/Dashicon/Dashicon';

registerStepType('send_email', {

	icon: ghEditor.steps.send_email.icon,
	group: ghEditor.steps.send_email.group,

	title: ({ data, context, settings }) => {

		if (!context || !context.email) {
			return <>{ 'Select and email to send...' }</>;
		}

		return <>{ 'Send' } <b>{ context.email.data.title }</b></>;
	},

	edit: ({ data, context, settings, updateSettings, commit, done }) => {

		const emailChanged = (email) => {
			updateSettings({
				email_id: email.ID,
			}, {
				email: email,
			});
		};

		return (
			<SimpleEditModal
				title={ settings.email_id
					? 'Send email...'
					: 'Select an email to send...' }
				done={ done }
				commit={ commit }
				showFooter={ typeof settings.email_id !== 'undefined' }
				modalProps={ {
					size: false,
					dialogClassName: !settings.email_id
						? 'modal-90w'
						: 'modal-md',
				} }
				modalBodyProps={ {
					className: 'no-padding',
				} }
			>
				{ !settings.email_id && <EmailSelector
					onSelect={ emailChanged }
				/> }
				{ settings.email_id && <>
					<div className={ 'btn-control-group aligncenter' }>
						<Button
							variant={ 'outline-secondary' }
							onClick={ () => emailChanged(
								false) }>{ 'Change Email' }</Button>
						<Button
							variant={ 'outline-primary' }
						>{ 'Edit Email' }</Button>
					</div>
					<div className={ 'main-preview-wrap' }>
						<EmailPreview
							email={ context.email }
						/>
					</div>
				</> }
			</SimpleEditModal>
		);
	},

});

const CancelToken = axios.CancelToken;
let cancel;

function getEmailsFromAPI (search) {

	cancel && cancel();

	return axios.get(groundhogg_endpoints.emails + '?search=' + search, {
		cancelToken: new CancelToken(function executor (c) {
			cancel = c;
		}),
	});
}

function EmailSelector ({ onSelect }) {

	const [emails, setEmails] = useState([]);
	const [search, setSearch] = useState('');
	const [isLoading, setIsLoading] = useState(false);
	const [initialLoad, setInitialLoad] = useState(false);

	const searchComplete = (emails) => {
		setEmails(emails);
		setIsLoading(false);
		setInitialLoad(true);
	};

	const searchForEmails = (search) => {
		setSearch(search);
		setIsLoading(true);
		getEmailsFromAPI(search).
			then(result => { searchComplete(result.data.emails); });
	};

	useEffect(() => {

		if (initialLoad) {
			return;
		}

		setIsLoading(true);
		getEmailsFromAPI('').
			then(result => { searchComplete(result.data.emails); });
	});

	return (
		<div className={ 'email-selector' }>
			<div className={ 'email-search' }>
				<Row className={ 'no-margins' }>
					<Col sm={ 8 }>
						<input
							placeholder={ 'Search for an email...' }
							type={ 'search' }
							value={ search }
							onChange={ (e) => searchForEmails(e.target.value) }
						/>
					</Col>
					<Col sm={ 4 }>
						<Button className={'alignright'} variant={'outline-primary'}>{ 'Create New Email' }</Button>
					</Col>
				</Row>
			</div>
			<div className={ 'emails-grid' }>
				<div className={ 'items-grid' }>
					{ emails.length > 0 &&
					emails.map(email => <EmailGridItem email={ email }
					                                   onSelect={ onSelect }/>) }
					{ !emails.length && !isLoading && <Alert
						variant={ 'danger' }>{ 'No emails were found.' }</Alert> }
					{ isLoading && <Spinner animation={ 'border' }/> }
				</div>
			</div>
		</div>
	);

}

function EmailGridItem ({ email, onSelect }) {

	const selected = () => {
		onSelect(email);
	};

	return (
		<div className={ 'grid-item' }>
			<div className={ 'email-item' }>
				<div className={ 'selectable' }>
					<div className={ 'selectable-actions btn-control-group' }>
						<Button
							variant={ 'outline-light' }
						    onClick={ selected }
							size={'lg'}
						>{ 'Use Email' }</Button>
						{/*<Button variant={ 'outline-light' }><Dashicon*/}
						{/*	icon={ 'visibility' }/>{ 'Preview' }</Button>*/}
					</div>
				</div>
				<div className={ 'email-item-details' }>
					<span
						className={ 'subject-line' }>{ email.data.subject }</span>
				</div>
				<div className={ 'email-item-content' }>
					<iframe
						src={ email.url }
						className={ 'email-preview' }
						scrolling={ 'no' }
					/>
				</div>
			</div>
		</div>
	);
}

function EmailPreview ({ email }) {
	return (
		<div className={ 'email-item' }>
			<div className={ 'email-item-details' }>
					<span
						className={ 'subject-line' }>{ email.data.subject }</span>
			</div>
			<div className={ 'email-item-content' }>
				<iframe
					src={ email.url }
					className={ 'email-preview main-preview' }
				/>
			</div>
		</div>
	);
}