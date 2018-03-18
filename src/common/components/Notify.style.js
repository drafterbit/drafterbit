module.exports = {
	notifyWrapper: {
		position: "fixed",
		top: "15%",
		left: "50%",
		transform: "translate(-50%, -50%)"
	},
	alertComponent: {
		borderRadius: "2px",
		minWidth: "360px"
	},
	notifyEnter: {
		top: 0,
		opacity: 0.01
	},
	notifyEnterActive: {
		top: "15%",
		opacity: 1,
		transition: 'all 400ms ease-in'
	},
	notifyLeave: {
		top: "15%",
		opacity: 1
	},
	notifyLeaveActive: {
		top: 0,
		opacity: 0.01,
		transition: 'all 200ms ease-in'
	},
	notifyAppear: {
		top: 0,
		opacity: 0.01
	},
	notifyAppearActive: {
		top: "15%",
		opacity: 1,
		transition: 'all 400ms ease-in'
	},
};