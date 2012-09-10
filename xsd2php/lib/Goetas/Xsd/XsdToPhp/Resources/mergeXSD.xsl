<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:env="goetas:envelope"
>

<xsl:template match="/">
	<env:env>
		<xsl:apply-templates />
	</env:env>
</xsl:template>

<xsl:template match="xsd:import[@schemaLocation]|xsd:include[@schemaLocation]">
	<xsl:variable name="doc" select="document(@schemaLocation)"/>	
	<xsl:apply-templates select="$doc"/>
</xsl:template>


<xsl:template match="wsdl:import[@location]">
	<xsl:variable name="doc" select="document(@location)"/>
	<xsl:apply-templates select="$doc"/>
</xsl:template>

<xsl:template match="node()|@*" priority="-4">
	<xsl:copy>
		<xsl:apply-templates  select="@*"/>
		<xsl:apply-templates />
	</xsl:copy>
</xsl:template>

</xsl:stylesheet>
