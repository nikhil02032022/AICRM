import './bootstrap';

// Alpine.js is NOT started by Livewire on public (unauthenticated) pages.
// This entry point starts Alpine independently for public web enquiry forms.
// Do NOT use this bundle on any page that also loads Livewire.
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const createPublicChatWidgetForm = ({ submitUrl, institutionName }) => ({
	sessionId: crypto.randomUUID(),
	institutionName,
	submitUrl,
	lead: {
		first_name: '',
		last_name: '',
		mobile: '',
		email: '',
		query_message: '',
		consent_given: false,
		consent_form_version: 'chat-widget-v1',
		source_url: document.referrer || window.location.href,
		source_utm_params: {},
	},
	submitting: false,
	error: '',
	success: '',

	init() {
		const params = new URLSearchParams(window.location.search);
		['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach((key) => {
			if (params.get(key)) {
				this.lead.source_utm_params[key] = params.get(key);
			}
		});
	},

	resetForm() {
		this.lead.first_name = '';
		this.lead.last_name = '';
		this.lead.mobile = '';
		this.lead.email = '';
		this.lead.query_message = '';
		this.lead.consent_given = false;
	},

	async submitLead() {
		this.error = '';
		this.success = '';
		this.submitting = true;

		const query = this.lead.query_message.trim();

		if (!query) {
			this.error = 'Please enter your query before submitting.';
			this.submitting = false;
			return;
		}

		const payload = {
			...this.lead,
			session_id: this.sessionId,
			transcript: [
				{ role: 'assistant', content: `Admissions support enquiry captured via ${this.institutionName} chatbot widget.` },
				{ role: 'user', content: query },
			],
		};

		try {
			const response = await fetch(this.submitUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					Accept: 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
				},
				body: JSON.stringify(payload),
			});

			const data = await response.json();

			if (response.ok && data.success) {
				this.success = 'Thanks, your enquiry was submitted successfully.';
				this.resetForm();
				this.sessionId = crypto.randomUUID();
			} else if (response.status === 422) {
				this.error = 'Please review your details and try again.';
			} else {
				this.error = data?.message || 'Unable to submit at the moment.';
			}
		} catch {
			this.error = 'Network error. Please try again.';
		} finally {
			this.submitting = false;
		}
	},
});

window.publicChatWidgetForm = createPublicChatWidgetForm;
Alpine.data('publicChatWidgetForm', createPublicChatWidgetForm);

const AUTO_IFRAME_SELECTOR = 'iframe[data-auto-resize-iframe]';
const MIN_IFRAME_HEIGHT = 520;

const readFrameHeight = (iframe) => {
	try {
		const doc = iframe.contentDocument || iframe.contentWindow?.document;

		if (!doc?.body || !doc?.documentElement) {
			return null;
		}

		return Math.max(
			doc.body.scrollHeight,
			doc.body.offsetHeight,
			doc.documentElement.scrollHeight,
			doc.documentElement.offsetHeight,
			MIN_IFRAME_HEIGHT,
		);
	} catch {
		return null;
	}
};

const resizeIframeToContent = (iframe) => {
	const height = readFrameHeight(iframe);

	if (height !== null) {
		iframe.style.height = `${height}px`;
	}
};

const wireAutoResizeIframe = (iframe) => {
	const runResize = () => resizeIframeToContent(iframe);

	iframe.addEventListener('load', () => {
		runResize();

		// Re-measure a few times after load for async field/render changes.
		[250, 700, 1200, 2000].forEach((delayMs) => {
			window.setTimeout(runResize, delayMs);
		});
	});

	window.addEventListener('resize', runResize);
	runResize();
};

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll(AUTO_IFRAME_SELECTOR).forEach((iframe) => {
		wireAutoResizeIframe(iframe);
	});
});

Alpine.start();
