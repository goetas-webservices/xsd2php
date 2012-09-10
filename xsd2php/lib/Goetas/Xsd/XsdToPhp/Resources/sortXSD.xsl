<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:env="goetas:envelope"
>


<xsl:key name="names" match="xsd:schema" use="@targetNamespace"/>

<xsl:template match="/">
	<env:env>
		<xsl:for-each select="//xsd:schema[generate-id() = generate-id(key('names',@targetNamespace)[1])]">
			<xsl:apply-templates select="."/>
		</xsl:for-each>
	</env:env>
</xsl:template>


<xsl:template match="node()|@*" priority="-4">
	<xsl:copy>
		<xsl:apply-templates  select="@*"/>
		<xsl:apply-templates />
	</xsl:copy>
</xsl:template>

</xsl:stylesheet>
