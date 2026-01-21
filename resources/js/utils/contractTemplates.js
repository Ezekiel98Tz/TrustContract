export const templates = {
  goods_sale: ({ title, buyerName, sellerName, amount, currency, deadline }) => [
    `This Goods Sale Agreement (“Agreement”) is between ${buyerName} (“Buyer”) and ${sellerName} (“Seller”).`,
    `Subject: ${title}.`,
    `Price: ${amount} ${currency}.`,
    deadline ? `Delivery deadline: ${deadline}.` : null,
    `Seller warrants that the goods are free from liens and conform to the described condition.`,
    `Buyer agrees to inspect upon delivery and notify Seller of any defects within 3 days.`,
    `Risk of loss transfers on delivery. Both parties agree to resolve disputes in good faith.`
  ].filter(Boolean).join('\n\n'),
  service_agreement: ({ title, buyerName, sellerName, amount, currency, deadline }) => [
    `This Service Agreement is between ${buyerName} (“Client”) and ${sellerName} (“Provider”).`,
    `Service: ${title}.`,
    `Fee: ${amount} ${currency}.`,
    deadline ? `Target completion date: ${deadline}.` : null,
    `Provider will deliver services in a professional and timely manner.`,
    `Client will provide necessary access and feedback.`,
    `Scope changes may affect timeline and fee; changes must be agreed in writing.`
  ].filter(Boolean).join('\n\n'),
  freelance_project: ({ title, buyerName, sellerName, amount, currency, deadline }) => [
    `This Freelance Project Agreement is between ${buyerName} (“Client”) and ${sellerName} (“Freelancer”).`,
    `Project: ${title}.`,
    `Compensation: ${amount} ${currency}.`,
    deadline ? `Milestone completion by: ${deadline}.` : null,
    `Freelancer retains portfolio rights; Client receives usage rights to deliverables upon full payment.`,
    `Confidentiality applies to all non-public information.`,
    `Any disputes will be addressed through negotiation before further escalation.`
  ].filter(Boolean).join('\n\n'),
  formal_goods_sale: ({ title, buyerName, sellerName, amount, currency, deadline }) => [
    `GOODS SALE AGREEMENT`,
    `Parties: ${buyerName} (“Buyer”) and ${sellerName} (“Seller”).`,
    `Subject Matter: ${title}.`,
    `Consideration: ${amount} ${currency}.`,
    deadline ? `Delivery: Seller shall deliver on or before ${deadline}.` : null,
    `Title and Risk: Title transfers upon full payment; risk transfers upon delivery.`,
    `Warranties: Seller warrants good title and conformity to agreed specifications.`,
    `Inspection: Buyer shall inspect upon delivery and notify defects within three (3) days.`,
    `Remedies: Nonconforming goods may be repaired, replaced, or refunded at Seller’s election.`,
    `Governing Law and Dispute Resolution: Parties shall first attempt good faith negotiation.`
  ].filter(Boolean).join('\n\n'),
  formal_service_agreement: ({ title, buyerName, sellerName, amount, currency, deadline }) => [
    `SERVICE AGREEMENT`,
    `Parties: ${buyerName} (“Client”) and ${sellerName} (“Provider”).`,
    `Scope of Services: ${title}.`,
    `Fees: ${amount} ${currency}.`,
    deadline ? `Schedule: Target completion by ${deadline}.` : null,
    `Standard: Provider shall perform with professional skill and care.`,
    `Change Control: Any change in scope, schedule, or fees requires written agreement.`,
    `Confidentiality: Both parties shall protect non-public information.`,
    `Intellectual Property: Deliverables become Client property upon full payment unless otherwise agreed.`,
    `Governing Law and Dispute Resolution: Parties shall first attempt good faith negotiation.`
  ].filter(Boolean).join('\n\n'),
  formal_freelance_project: ({ title, buyerName, sellerName, amount, currency, deadline }) => [
    `FREELANCE PROJECT AGREEMENT`,
    `Parties: ${buyerName} (“Client”) and ${sellerName} (“Freelancer”).`,
    `Project: ${title}.`,
    `Compensation: ${amount} ${currency}.`,
    deadline ? `Milestones: Completion by ${deadline} unless adjusted by mutual agreement.` : null,
    `Ownership: Client receives usage rights upon full payment; Freelancer may showcase non-confidential work in portfolio.`,
    `Confidentiality: Applies to all proprietary information.`,
    `Indemnity: Freelancer warrants original work and non-infringement.`,
    `Governing Law and Dispute Resolution: Parties shall first attempt good faith negotiation.`
  ].filter(Boolean).join('\n\n'),
};

export function composeTemplate(key, ctx) {
  const fn = templates[key];
  if (!fn) return '';
  return fn(ctx);
}
