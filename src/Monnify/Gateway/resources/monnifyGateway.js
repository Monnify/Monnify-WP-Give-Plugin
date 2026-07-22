(function () {
	"use strict";

	var el = window.React.createElement;
	var __ = window.wp.i18n.__;

	var monnifyGateway = {
		id: "monnify",
		beforeCreatePayment: async function (values) {
			return Object.assign({}, values);
		},
		Fields: function () {
			return el(
				"div",
				{ style: { textAlign: "center" } },
				el("img", {
					src: window.GiveMonnifyGatewayData.logoUrl,
					alt: "Monnify",
					style: { maxWidth: "200px" },
				}),
				el("br"),
				el("br"),
				el(
					"p",
					{ style: { fontSize: "0.9rem" } },
					el(
						"strong",
						null,
						__("Make your donation quickly and securely with Monnify", "give-monnify")
					)
				),
				el(
					"p",
					{ style: { fontSize: "0.8rem" } },
					el("strong", null, __("How it works:", "give-monnify")),
					" ",
					__(
						"You will be redirected to Monnify to securely complete your donation, then brought back to this site to view your receipt.",
						"give-monnify"
					)
				)
			);
		},
	};

	window.givewp.gateways.register(monnifyGateway);
})();
