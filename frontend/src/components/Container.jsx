export default function Container({ as: Tag = "div", className = "", children }) {
  const classes = ["container-shell", className].filter(Boolean).join(" ");

  return <Tag className={classes}>{children}</Tag>;
}
