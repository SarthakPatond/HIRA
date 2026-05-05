import Container from "./Container";

export default function Section({
  as: Tag = "section",
  className = "",
  containerClassName = "",
  children,
  ...props
}) {
  const classes = ["section-pad", className].filter(Boolean).join(" ");

  return (
    <Tag className={classes} {...props}>
      <Container className={containerClassName}>{children}</Container>
    </Tag>
  );
}
